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
     * @var array The cached user info of the current user.
     */
    protected $_userInfo;
    
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
        $this->_userInfo = $this->setUserInfo();
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
        $this->_userInfo = $this->setUserInfo();
    }
    
    /**
     * Logout via the MediaWiki service.
     */
    public function logout()
    {
        $this->_mediawiki->logout();
        $this->_userInfo = $this->setUserInfo();
    }
    
    /**
     * Determine if the current user is logged in by checking against the user 
     * ID. An anonymous user has an ID of 0.
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        return (bool) $this->_userInfo['id'];
    }
    
    /**
     * Set information about the currently logged-in user.
     */
    public function setUserInfo()
    {
        $userInfo = $this->_mediawiki->getUserInfo()->query->userinfo;
        $this->_userInfo = array('id'         => $userInfo->id, 
                                 'name'       => $userInfo->name, 
                                 'rights'     => $userInfo->rights, 
                                 'edit_count' => $userInfo->editcount, 
                                 'email'      => $userInfo->email);
    }
    
    /**
     * Return information about the currently logged-in user.
     * 
     * @return array
     */
    public function getUserInfo()
    {
        return $this->_userInfo;
    }
    
    /**
     * Return the specified user's contributions.
     * 
     * @param null|string $username
     * @param int $limit
     * @return array
     */
    public function getUserContributions($username = null, $limit = 10)
    {
        // If no username was specified, set it to the current user.
        if (null === $username) {
            $username = $this->_userInfo['name'];
        }
        
        $userContribs = $this->_mediawiki->getUserContributions($username, $limit)
                                         ->query
                                         ->usercontribs;
        $userContributions = array();
        foreach ($userContribs as $value) {
            
            // Filter out contributions that are not document pages. 
            if (self::BASE_TITLE_PREFIX != $value->title[0]) {
                continue;
            }
            
            $document = self::decodeBaseTitle($value->title);
            
            $userContributions[] = array('page_id'          => $value->pageid, 
                                         'revision_id'      => $value->revid, 
                                         'title'            => $value->title, 
                                         'document_id'      => $document[0], 
                                         'document_page_id' => $document[1], 
                                         'timestamp'        => $value->timestamp, 
                                         'comment'          => $value->comment, 
                                         'size'             => $value->size);
        }
        return $userContributions;
    }
}