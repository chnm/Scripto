<?php
/**
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Scripto/Exception.php';

/**
 * Represents a Scripto application. Serves as a connector object between the 
 * external system API and MediaWiki API.
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
            $this->_mediawiki =  new Scripto_Service_MediaWiki($mediawiki['api_url'], 
                                                               $mediawiki['db_name'], 
                                                               (bool) $mediawiki['pass_cookies']);
        } else {
            throw new Scripto_Exception('The provided mediawiki parameter is invalid.');
        }
        
        // Set the user information.
        $this->setUserInfo();
    }
    
    /**
     * Check whethe the specified document exists.
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
        $this->_userInfo = $this->_mediawiki->getUserInfo();
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
     * @param int $limit
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
            $userContribs = $this->_mediawiki->getUserContributions(
                $this->_userInfo['query']['userinfo']['name'], 
                $start, 
                100
            );
            foreach ($userContribs['query']['usercontribs'] as $value) {
                
                // Filter out duplicate pages.
                if (array_key_exists($value['pageid'], $userDocumentPages)) {
                    continue;
                }
                
                // Filter out contributions that are not document pages. 
                if (Scripto_Document::BASE_TITLE_PREFIX != $value['title'][0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $document = Scripto_Document::decodeBaseTitle($value['title']);
                
                // Set the document title. Reduce calls to the adapter by 
                // caching each title and checking if it already exists.
                if (array_key_exists($document[0], $documentTitles)) {
                    $documentTitle = $documentTitles[$document[0]];
                } else {
                    $documentTitle = $this->_adapter->getDocumentTitle($document[0]);
                    $documentTitles[$document[0]] = $documentTitle;
                }
                
                // Build the user document pages, newest properties first.
                $userDocumentPages[$value['pageid']] = array(
                    'revision_id'      => $value['revid'], 
                    'mediawiki_title'  => $value['title'], 
                    'timestamp'        => $value['timestamp'], 
                    'comment'          => $value['comment'], 
                    'size'             => $value['size'], 
                    'document_id'      => $document[0], 
                    'document_page_id' => $document[1], 
                    'document_title'   => $documentTitle
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($userDocumentPages)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($userContribs['query-continue'])) {
                $start = $userContribs['query-continue']['usercontribs']['ucstart'];
            } else {
                $start = null;
            }
            
        } while ($start);
        
        return $userDocumentPages;
    }
    
    /**
     * Get the recent changes.
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentChanges($limit = 10)
    {
        require_once 'Scripto/Document.php';
        
        $limit = (int) $limit;
        $recentChanges = array();
        $documentTitles = array();
        $start = null;
        do {
            $changes = $this->_mediawiki->getRecentChanges(
                array('rctype'      => 'edit|new', 
                      'rcprop'      => 'user|timestamp|title|ids', 
                      'rcnamespace' => '0', 
                      'rcstart'     => $start)
            );
            foreach ($changes['query']['recentchanges'] as $value) {
                
                // Filter out changes that are not document pages. 
                if (Scripto_Document::BASE_TITLE_PREFIX != $value['title'][0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $document = Scripto_Document::decodeBaseTitle($value['title']);
                
                // Set the document title. Reduce calls to the adapter by 
                // caching each title and checking if it already exists.
                if (array_key_exists($document[0], $documentTitles)) {
                    $documentTitle = $documentTitles[$document[0]];
                } else {
                    $documentTitle = $this->_adapter->getDocumentTitle($document[0]);
                    $documentTitles[$document[0]] = $documentTitle;
                }
                
                $recentChanges[] = array(
                    'type'             => $value['type'], 
                    'page_id'          => $value['pageid'], 
                    'revision_id'      => $value['revid'], 
                    'old_revision_id'  => $value['old_revid'], 
                    'mediawiki_title'  => $value['title'], 
                    'timestamp'        => $value['timestamp'], 
                    'user'             => $value['user'], 
                    'document_id'      => $document[0], 
                    'document_page_id' => $document[1], 
                    'document_title'   => $documentTitle
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($recentChanges)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($changes['query-continue'])) {
                $start = $changes['query-continue']['recentchanges']['rcstart'];
            } else {
                $start = null;
            }
            
        } while ($start);
        
        return $recentChanges;
    }
    
    /**
     * Get the diff between two page revisions.
     * 
     * @param string $baseTitle
     * @param string $revisionId The revision ID to diff.
     * @param string $compareTo The revision to diff to: use the revision ID, 
     * prev, next, or cur.
     */
    function getDiff($baseTitle, $revisionId, $diffTo = 'prev')
    {
        $revision = $this->_mediawiki->getRevisions(
            $baseTitle, 
            array('rvstartid' => $revisionId, 
                  'rvdiffto'  => $diffTo, 
                  'rvlimit'   => '1')
        );
        $page = current($revision['query']['pages']);
        return '<table>' . $page['revisions'][0]['diff']['*'] . '</table>';
    }
}
