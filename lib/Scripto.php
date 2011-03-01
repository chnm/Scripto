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
     * @param string $mediaWikiApiUrl The MediaWiki API URL.
     * @param string $mediaWikiDbName The MediaWiki database name.
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param bool $passCookies Pass cookies to the web browser via API client.
     */
    public function __construct(Scripto_Adapter_Interface $adapter, 
                                $mediaWikiApiUrl, 
                                $mediaWikiDbName, 
                                $passCookies = true)
    {
        $this->_adapter = $adapter;
        
        require_once 'Scripto/Service/MediaWiki.php';
        $this->_mediawiki = new Scripto_Service_MediaWiki($mediaWikiApiUrl, 
                                                          $mediaWikiDbName, 
                                                          (bool) $passCookies);
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
     * Determine if the current user is logged in.
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->_mediawiki->isLoggedIn();
    }
    
    /**
     * Return information about the currently logged-in user.
     * 
     * @return stdClass
     */
    public function getUserInfo()
    {
        // If not already cached, set the current user info.
        if (null === $this->_userInfo) {
            $userInfo = $this->_mediawiki->getUserInfo()->query->userinfo;
            $this->_userInfo = array('id'         => $userInfo->id, 
                                     'name'       => $userInfo->name, 
                                     'rights'     => $userInfo->rights, 
                                     'edit_count' => $userInfo->editcount, 
                                     'email'      => $userInfo->email);
        }
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
            $userInfo = $this->getUserInfo();
            $username = $userInfo['name'];
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