<?php
/**
 * @package Scripto
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Require the base Scripto exception.
 */
require_once 'Scripto/Exception.php';

/**
 * Represents a Scripto application. Serves as a connector object between the 
 * external system API and MediaWiki API.
 * 
 * @package Scripto
 */
class Scripto
{
    /**
     * @var Scripto_Adapter_Interface The adapter object for the external 
     * system.
     */
    protected $_adapter;
    
    /**
     * @var Scripto_Service_MediaWiki The MediaWiki service object.
     */
    protected $_mediawiki;
    
    /**
     * @var array
     */
    protected $_userInfo;
    
    /**
     * Construct the Scripto object.
     * 
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param array|Scripto_Service_MediaWiki $mediawiki If an array:
     *     $mediawiki['api_url']: required; the MediaWiki API URL
     *     $mediawiki['db_name']: required; the MediaWiki database name
     *     $mediawiki['pass_cookies']: optional pass cookies to the web browser 
     *     via API client
     */
    public function __construct(Scripto_Adapter_Interface $adapter, $mediawiki)
    {
        // Set the adapter.
        $this->_adapter = $adapter;
        
        // Set the MediaWiki service.
        if ($mediawiki instanceof Scripto_Service_MediaWiki) {
            $this->_mediawiki = $mediawiki;
        } else if (is_array($mediawiki) 
                   && array_key_exists('api_url', $mediawiki) 
                   && array_key_exists('db_name', $mediawiki)) {
            if (!isset($mediawiki['pass_cookies'])) {
                $mediawiki['pass_cookies'] = true;
            }
            require_once 'Scripto/Service/MediaWiki.php';
            $this->_mediawiki = new Scripto_Service_MediaWiki($mediawiki['api_url'], 
                                                              $mediawiki['db_name'], 
                                                              (bool) $mediawiki['pass_cookies']);
        } else {
            throw new Scripto_Exception('The provided mediawiki parameter is invalid.');
        }
        
        // Set the user information.
        $this->setUserInfo();
    }
    
    /**
     * Check whether the specified document exists in the external system.
     * 
     * @param string|int $id The unique document identifier.
     * @return bool
     */
    public function documentExists($id)
    {
        // Query the adapter whether the document exists.
        if ($this->_adapter->documentExists($id)) {
            return true;
        }
        return false;
    }
    
    /**
     * Get a Scripto_Document object.
     * 
     * @param string|int $id The unique document identifier.
     * @return Scripto_Document
     */
    public function getDocument($id)
    {
        require_once 'Scripto/Document.php';
        return new Scripto_Document($id, $this->_adapter, $this->_mediawiki);
    }
    
    /**
     * Login via the MediaWiki service.
     * 
     * It is possible to restrict account creation in MediaWiki.
     * @link http://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_account_creation
     * 
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        $this->_mediawiki->login($username, $password);
        $this->setUserInfo();
    }
    
    /**
     * Logout via the MediaWiki service.
     */
    public function logout()
    {
        $this->_mediawiki->logout();
        $this->setUserInfo();
    }
    
    /**
     * Determine if the current user is logged in.
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        // Check against the user ID. An anonymous user has an ID of 0.
        return (bool) $this->_userInfo['query']['userinfo']['id'];
    }
    
    /**
     * Determine if the current user can export transcriptions to the external 
     * system.
     * 
     * @param array $groups The MediaWiki groups allowed to export.
     * @return bool
     */
    public function canExport(array $groups = array('sysop', 'bureaucrat'))
    {
        foreach ($groups as $group) {
            if (in_array($group, $this->_userInfo['query']['userinfo']['groups'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Determine if the current user can protect the MediaWiki page.
     * 
     * @return bool
     */
    public function canProtect()
    {
        // Users with protect rights can protect pages.
        if (in_array('protect', $this->_userInfo['query']['userinfo']['rights'])) {
            return true;
        }
        return false;
    }
    
    /**
     * Set the current user's information.
     * 
     * Under normal circumstances calling this method directly is unnecessary, 
     * but is helpful when authenticating after construction and when a login is 
     * not called, like when hijacking cookies for command line authentication.
     */
    public function setUserInfo()
    {
        $this->_userInfo = $this->_mediawiki->getUserInfo('groups|rights');
    }
    
    /**
     * Return the name of the current user.
     * 
     * @return string
     */
    public function getUserName()
    {
        return $this->_userInfo['query']['userinfo']['name'];
    }
    
    /**
     * Get the current user's most recently contributed document pages.
     * 
     * @param int $limit The number of document pages to return.
     * @return array
     */
    public function getUserDocumentPages($limit = 10)
    {
        require_once 'Scripto/Document.php';
        
        $limit = (int) $limit;
        $userDocumentPages = array();
        $documentTitles = array();
        $start = null;
        do {
            $response = $this->_mediawiki->getUserContributions(
                $this->_userInfo['query']['userinfo']['name'], 
                array('ucstart' => $start, 
                      'uclimit' => 100)
            );
            foreach ($response['query']['usercontribs'] as $value) {
                
                // Filter out duplicate pages.
                if (array_key_exists($value['pageid'], $userDocumentPages)) {
                    continue;
                }
                
                // Preempt further processing on contributions with an invalid 
                // prefix.
                if (Scripto_Document::BASE_TITLE_PREFIX != $value['title'][0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $documentIds = Scripto_Document::decodeBaseTitle($value['title']);
                
                // Set the document title. Reduce calls to the adapter by 
                // caching each title and checking if it already exists.
                if (array_key_exists($documentIds[0], $documentTitles)) {
                    $documentTitle = $documentTitles[$documentIds[0]];
                } else {
                    // Before getting the title, filter out contributions that 
                    // are not valid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                }
                
                // Build the user document pages, newest properties first.
                $userDocumentPages[$value['pageid']] = array(
                    'revision_id'      => $value['revid'], 
                    'mediawiki_title'  => $value['title'], 
                    'timestamp'        => $value['timestamp'], 
                    'comment'          => $value['comment'], 
                    'size'             => $value['size'], 
                    'document_id'      => $documentIds[0], 
                    'document_page_id' => $documentIds[1], 
                    'document_title'   => $documentTitle
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($userDocumentPages)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($response['query-continue'])) {
                $start = $response['query-continue']['usercontribs']['ucstart'];
            } else {
                $start = null;
            }
            
        } while ($start);
        
        return $userDocumentPages;
    }
    
    /**
     * Get the recent changes.
     * 
     * @link http://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces
     * @param int $limit The number of recent changes to return.
     * @return array
     */
    public function getRecentChanges($limit = 10)
    {
        require_once 'Scripto/Document.php';
        
        $start = null;
        $recentChanges = array();
        $documentTitles = array();
        
        // Namespaces to get: ns_index => ns_name
        // See http://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces
        $namespaces = array('0' => 'Main', '1' => 'Talk');
        
        do {
            $response = $this->_mediawiki->getRecentChanges(
                array('rcprop'      => 'user|comment|timestamp|title|ids|sizes|loginfo|flags', 
                      'rclimit'     => '100', 
                      'rcnamespace' => implode('|', array_keys($namespaces)), 
                      'rcstart'     => $start)
            );
        
            foreach ($response['query']['recentchanges'] as $value) {
                
                // Extract the title, removing the namespace if any.
                $title = preg_replace('/^(.+:)?(.+)$/', '$2', $value['title']);
                
                // Preempt further processing on contributions with an invalid 
                // prefix.
                if (Scripto_Document::BASE_TITLE_PREFIX != $title[0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $documentIds = Scripto_Document::decodeBaseTitle($title);
                
                // Set the document title. Reduce calls to the adapter by 
                // caching each title and checking if it already exists.
                if (array_key_exists($documentIds[0], $documentTitles)) {
                    $documentTitle = $documentTitles[$documentIds[0]];
                } else {
                    // Before getting the title, filter out contributions that 
                    // are not valid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                }
                
                $recentChanges[] = array(
                    'type'             => $value['type'], 
                    'namespace_index'  => $value['ns'], 
                    'namespace_name'   => $namespaces[$value['ns']], 
                    'mediawiki_title'  => $value['title'], 
                    'rcid'             => $value['rcid'], 
                    'page_id'          => $value['pageid'], 
                    'revision_id'      => $value['revid'], 
                    'old_revision_id'  => $value['old_revid'], 
                    'user'             => $value['user'], 
                    'old_length'       => $value['oldlen'], 
                    'new_length'       => $value['newlen'], 
                    'timestamp'        => $value['timestamp'], 
                    'comment'          => $value['comment'], 
                    'log_id'           => isset($value['logid']) ? $value['logid']: null, 
                    'log_type'         => isset($value['logtype']) ? $value['logtype']: null, 
                    'log_action'       => isset($value['logaction']) ? $value['logaction']: null, 
                    'new'              => isset($value['new']) ? true: false, 
                    'minor'            => isset($value['minor']) ? true: false, 
                    'document_id'      => $documentIds[0], 
                    'document_page_id' => $documentIds[1], 
                    'document_title'   => $documentTitle, 
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($recentChanges)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($response['query-continue'])) {
                $start = $response['query-continue']['recentchanges']['rcstart'];
            } else {
                $start = null;
            }
            
        } while ($start);
        
        return $recentChanges;
    }
    
    /**
     * Get the difference between two page revisions.
     * 
     * @param int $from The revision ID from which to diff.
     * @param int|string $to The revision to which to diff. Use the revision ID, 
     * "prev", "next", or "cur".
     * @return string
     */
    public function getRevisionDiff($fromRevisionId, $toRevisionId = 'prev')
    {
        return $this->_mediawiki->getRevisionDiff($fromRevisionId, $toRevisionId);
    }
    
    /**
     * Get properties of the specified page revision.
     * 
     * @param int $revisionId
     * @return array
     */
    public function getRevision($revisionId)
    {
        // Get the revision properties.
        $response = $this->_mediawiki->getRevisions(
            null, 
            array('revids' => $revisionId, 
                  'rvprop' => 'ids|flags|timestamp|user|comment|size|content')
        );
        $page = current($response['query']['pages']);
        
        // Parse the wikitext into HTML.
        $response = $this->_mediawiki->parse(
            array('text' => '__NOEDITSECTION__' . $page['revisions'][0]['*'])
        );
        
        $revision = array('revision_id' => $page['revisions'][0]['revid'], 
                          'parent_id'   => $page['revisions'][0]['parentid'], 
                          'user'        => $page['revisions'][0]['user'], 
                          'timestamp'   => $page['revisions'][0]['timestamp'], 
                          'comment'     => $page['revisions'][0]['comment'], 
                          'size'        => $page['revisions'][0]['size'], 
                          'wikitext'    => $page['revisions'][0]['*'], 
                          'html'        => $response['parse']['text']['*']);
        return $revision;
   }
}
