<?php
/**
 * @copyright © 2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Scripto/Exception.php';

/**
 * Base Scripto class. Serves as a connector object between the external system 
 * API and MediaWiki API.
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
     * Construct the Scripto object.
     * 
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param array|Scripto_Service_MediaWiki $mediawiki {@link Scripto::mediawikiFactory()}
     */
    public function __construct(Scripto_Adapter_Interface $adapter, $mediawiki)
    {
        $this->_adapter = $adapter;
        $this->_mediawiki = self::mediawikiFactory($mediawiki);
    }
    
    /**
     * Build and return a MediaWiki service object.
     * 
     * @param array|Scripto_Service_MediaWiki $mediawiki If an array:
     *     $mediawiki['api_url']: required; the MediaWiki API URL
     *     $mediawiki['db_name']: required; the MediaWiki database name
     *     $mediawiki['pass_cookies']: optional pass cookies to the web browser 
     *     via API client
     * @return Scripto_Service_MediaWiki|false
     */
    public static function mediawikiFactory($mediawiki)
    {
        if ($mediawiki instanceof Scripto_Service_MediaWiki) {
            return $mediawiki;
        }
        
        if (is_array($mediawiki) 
         && array_key_exists('api_url', $mediawiki) 
         && array_key_exists('db_name', $mediawiki)) {
            if (!isset($mediawiki['pass_cookies'])) {
                $mediawiki['pass_cookies'] = true;
            }
            require_once 'Scripto/Service/MediaWiki.php';
            return new Scripto_Service_MediaWiki($mediawiki['api_url'], 
                                                 $mediawiki['db_name'], 
                                                 (bool) $mediawiki['pass_cookies']);
        }
        
        throw new Scripto_Exception('The provided mediawiki parameter is invalid.');
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
    }
    
    /**
     * Logout via the MediaWiki service.
     */
    public function logout()
    {
        $this->_mediawiki->logout();
    }
    
    /**
     * Determine if the current user is logged in by checking against the user 
     * ID. An anonymous user has an ID of 0.
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        $userInfo = $this->_mediawiki->getUserInfo();
        return (bool) $userInfo['query']['userinfo']['id'];
    }
    
    /**
     * Determine if the current user can export transcriptions to the external 
     * system.
     * 
     * @return bool
     */
    public function canExport()
    {
        $userInfo = $this->_mediawiki->getUserInfo();
        if (in_array('sysop', $userInfo['query']['userinfo']['groups'])) {
            return true;
        }
        return false;
    }
    
    /**
     * Return the name of the current user.
     * 
     * @return string
     */
    public function getUserName()
    {
        $userInfo = $this->_mediawiki->getUserInfo();
        return $userInfo['query']['userinfo']['name'];
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
        $userInfo = $this->_mediawiki->getUserInfo();
        $userDocumentPages = array();
        $documentTitles = array();
        $start = null;
        do {
            $userContribs = $this->_mediawiki->getUserContributions($userInfo['query']['userinfo']['name'], 
                                                                    $start, 
                                                                    100);
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
                $userDocumentPages[$value['pageid']] = array('revision_id'      => $value['revid'], 
                                                             'mediawiki_title'  => $value['title'], 
                                                             'timestamp'        => $value['timestamp'], 
                                                             'comment'          => $value['comment'], 
                                                             'size'             => $value['size'], 
                                                             'document_id'      => $document[0], 
                                                             'document_page_id' => $document[1], 
                                                             'document_title'   => $documentTitle);
                
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
}