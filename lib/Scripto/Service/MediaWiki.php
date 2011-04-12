<?php
/**
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Zend/Service/Abstract.php';
require_once 'Zend/Http/Cookie.php';
require_once 'Scripto/Service/Exception.php';

/**
 * MediaWIki API client.
 */
class Scripto_Service_MediaWiki extends Zend_Service_Abstract
{
    /**
     * The cookie name prefix, used to namespace Scripto/MediaWiki cookies when 
     * passed to the browser.
     */
    const COOKIE_PREFIX = 'scripto_';
    
    /**
     * @var string The MediaWiki database name, used to namespace Scripto/
     * MediaWiki cookies.
     */
    protected $_dbName;
    
    /**
     * @var bool Pass Scripto cookies to the web browser.
     */
    protected $_passCookies;
    
    /**
     * @var array Scripto/MediaWiki cookie name suffixes.
     */
    protected $_cookieSuffixes = array('_session', 'UserID', 'UserName', 'Token');
    
    /**
     * @param string $apiUrl
     * @param string $dbName The name of the MediaWiki database.
     * @param bool $passCookies Pass cookies to the web browser.
     */
    public function __construct($apiUrl, $dbName, $passCookies = true)
    {
        $this->_dbName = (string) $dbName;
        $this->_passCookies = (bool) $passCookies;
        
        // Set the HTTP client for the MediaWiki API .
        self::getHttpClient()->setUri($apiUrl)
                             ->setConfig(array('keepalive' => true))
                             ->setCookieJar();
        
        // If MediaWiki API authentication cookies are being passed, get them 
        // from the browser and add them to the HTTP client cookie jar. Doing so 
        // maintains state between browser requests. 
        if ($this->_passCookies) {
            foreach ($this->_cookieSuffixes as $cookieSuffix) {
                $cookieName = self::COOKIE_PREFIX . $this->_dbName . $cookieSuffix;
                if (array_key_exists($cookieName, $_COOKIE)) {
                    $cookie = new Zend_Http_Cookie($this->_dbName . $cookieSuffix, 
                                                   $_COOKIE[$cookieName], 
                                                   self::getHttpClient()->getUri()->getHost());
                    self::getHttpClient()->getCookieJar()->addCookie($cookie);
                }
            }
        }
    }
    
    /**
     * Log into MediaWiki to access protected API actions.
     * 
     * @link http://www.mediawiki.org/wiki/API:Login
     * @param string $username The user's username.
     * @param string $password The user's password.
     */
    public function login($username, $password)
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'login')
                             ->setParameterPost('lgname', $username)
                             ->setParameterPost('lgpassword', $password);
        $response = $this->_request('POST', 'json');
        
        // Confirm the login token.
        if ('NeedToken' == $response['login']['result']) {
            self::getHttpClient()->setParameterPost('format', 'json')
                                 ->setParameterPost('action', 'login')
                                 ->setParameterPost('lgname', $username)
                                 ->setParameterPost('lgpassword', $password)
                                 ->setParameterPost('lgtoken', $response['login']['token']);
            
            $response = $this->_request('POST', 'json');
        }
        
        // Process a successful login.
        if ('Success' == $response['login']['result']) {
            if ($this->_passCookies) {
                // Persist MediaWiki authentication cookies in the browser.
                foreach (self::getHttpClient()->getCookieJar()->getAllCookies() as $cookie) {
                    setcookie(self::COOKIE_PREFIX . $cookie->getName(), 
                              $cookie->getValue(), 
                              $cookie->getExpiryTime());
                }
            }
            return;
        }
        
        // Process an unsuccessful login.
        $errors = array('NoName'          => 'Username is empty.', 
                        'Illegal'         => 'Username is illegal.', 
                        'NotExists'       => 'Username is not found.', 
                        'EmptyPass'       => 'Password is empty.', 
                        'WrongPass'       => 'Password is incorrect.', 
                        'WrongPluginPass' => 'Password is incorrect (via plugin)', 
                        'CreateBlocked'   => 'IP address is blocked for account creation.', 
                        'Throttled'       => 'Login attempt limit surpassed.', 
                        'Blocked'         => 'User is blocked.');
        
        $error = $response['login']['result'];
        if (array_key_exists($error, $errors)) {
            throw new Scripto_Service_Exception($errors[$error]);
        }
        throw new Scripto_Service_Exception("Unknown login error: '{$response['login']['result']}'");
    }
    
    /**
     * Log out of MediaWiki.
     * 
     * @link http://www.mediawiki.org/wiki/API:Logout
     */
    public function logout()
    {
        self::getHttpClient()->setParameterPost('action', 'logout');
        self::getHttpClient()->request('POST');
        
        // Reset the cookie jar.
        self::getHttpClient()->getCookieJar()->reset();
        
        if ($this->_passCookies) {
            // Delete the MediaWiki authentication cookies from the browser.
            foreach ($this->_cookieSuffixes as $cookieSuffix) {
                $cookieName = self::COOKIE_PREFIX . $this->_dbName . $cookieSuffix;
                if (array_key_exists($cookieName, $_COOKIE)) {
                    setcookie($cookieName, false);
                }
            }
        }
    }
    
    /**
     * Get the MediaWiki page wikitext for a specified title.
     * 
     * @link http://www.mediawiki.org/wiki/API:Query#Exporting_pages
     * @param string $title The title of the page.
     * @return string The wikitext of the page.
     */
    public function getPageWikitext($title)
    {
        // Not available in JSON format.
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('titles', $title)
                             ->setParameterPost('export', true)
                             ->setParameterPost('exportnowrap', true);
        $response = $this->_request('POST', 'xml');
        
        $text = null;
        if (isset($response->page->revision->text)) {
            $text = (string) $response->page->revision->text;
        }
        return $text;
    }
    
    /**
     * Get parsed wikitext.
     * 
     * @param array $params
     * @return array
     */
    public function getParse(array $params = array())
    {
        $paramNames = array('text', 'title', 'page', 'prop', 'pst', 'uselang');
        foreach ($paramNames as $paramName) {
            if (array_key_exists($paramName, $params)) {
                self::getHttpClient()->setParameterPost($paramName, $params[$paramName]);
            }
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'parse');
        $response = $this->_request('POST', 'json');
        return $response;
    }
    
    /**
     * Get an HTML preview of the provided wikitext.
     * 
     * @link http://www.mediawiki.org/wiki/API:Parsing_wikitext#parse
     * @param string $wikitext The wikitext.
     * @return string The wikitext parsed as HTML.
     */
    public function getPreview($wikitext)
    {
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'parse')
                             ->setParameterPost('text', '__NOEDITSECTION__' . $wikitext);
        
        $response = $this->_request('POST', 'xml');
        return (string) $response->parse->text;
    }
    
    /**
     * Get the necessary credentials to edit the current MediaWiki page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Edit
     * @param string $title
     * @return array|null An array containing the edittoken and basetimestamp
     */
    public function getEditCredentials($title)
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('prop', 'info|revisions')
                             ->setParameterPost('intoken', 'edit')
                             ->setParameterPost('titles', $title);
        
        $response = json_decode(self::getHttpClient()->request('POST')->getBody(), true);
        self::getHttpClient()->resetParameters();
        
        $page = current($response['query']['pages']);
        
        // Extract the edittoken. Return NULL if edittoken doesn't exist.
        if (!isset($page['edittoken'])) {
            return null;
        }
        $edittoken = $page['edittoken'];
        
        // Extract the timestamp of the last revision to protect against edit 
        // conflicts. This API call returns only the last revision.
        $basetimestamp = null;
        if (!isset($page['revisions'])) {
            $basetimestamp = $page['revisions']['timestamp'];
        }
        return array('edittoken' => $edittoken, 'basetimestamp' => $basetimestamp);
    }
    
    /**
     * Edit the MediaWiki page for the current document page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Edit
     * @param string $title The title of the page to edit
     * @param string $text The wikitext of the page
     */
    public function editPage($title, $text)
    {
        // Get the edit token and base timestamp.
        $credentials = $this->getEditCredentials($title);
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'edit')
                             ->setParameterPost('title', $title)
                             ->setParameterPost('text', $text)
                             ->setParameterPost('token', $credentials['edittoken'])
                             ->setParameterPost('basetimestamp', $credentials['basetimestamp']);
        $this->_request('POST', 'json');
    }
    
    
    /**
     * Return a protect token.
     * 
     * @link http://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @return string|null
     */
    public function getProtectToken($title)
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('prop', 'info')
                             ->setParameterPost('intoken', 'protect')
                             ->setParameterPost('titles', $title);
        $response = $this->_request('POST', 'json');
        
        $page = current($response['query']['pages']);
        if (!isset($page['protecttoken'])) {
            return null;
        }
        return $page['protecttoken'];
    }
    
    /**
     * Determine whether a page is created.
     * 
     * @param string $title
     * @return bool
     */
    public function pageCreated($title)
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('titles', $title);
        $response = $this->_request('POST', 'json');
        
        $page = current($response['query']['pages']);
        if (isset($page['missing'])) {
            return false;
        }
        return true;
    }
    
    /**
     * Protect a page.
     * 
     * If the page has not been created, protect it from creation. If the page 
     * has been created, protect it from editing.
     * 
     * @link http://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @param string $protectToken
     */
    public function protectPage($title, $protectToken)
    {
        // Get the protect token.
        $protectToken = $this->getProtectToken($title);
        
        // Set the protections depending on whether the page has been created.
        if ($this->pageCreated($title)) {
            $protections = 'edit=sysop';
        } else {
            $protections = 'create=sysop';
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'protect')
                             ->setParameterPost('title', $title)
                             ->setParameterPost('token', $protectToken)
                             ->setParameterPost('protections', $protections);
        $this->_request('POST', 'json');
    }
    
    /**
     * Unprotect a page.
     * 
     * If the page has not been created, unprotect creation. If the page has 
     * been created, unprotect editing.
     * 
     * @link http://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @param string $protectToken
     */
    public function unprotectPage($title, $protectToken)
    {
        // Get the protect token.
        $protectToken = $this->getProtectToken($title);
        
        // Set the protections depending on whether the page has been created.
        if ($this->pageCreated($title)) {
            $protections = 'edit=all';
        } else {
            $protections = 'create=all';
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'protect')
                             ->setParameterPost('title', $title)
                             ->setParameterPost('token', $protectToken)
                             ->setParameterPost('protections', $protections);
        $this->_request('POST', 'json');
    }
    
    /**
     * Return information about the currently logged-in user.
     * 
     * @link http://www.mediawiki.org/wiki/API:Meta#userinfo_.2F_ui
     * @return array
     */
    public function getUserInfo()
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('meta', 'userinfo')
                             ->setParameterPost('uiprop', 'rights|editcount|email|groups|blockinfo|hasmsg|changeablegroups|options|ratelimits');
        $response = $this->_request('POST', 'json');
        return $response;
    }
    
    /**
     * Return the specified user's contributions.
     * 
     * @link http://www.mediawiki.org/wiki/API:Usercontribs
     * @param null|string $ucuser
     * @param array $params
     * @return array
     */
    public function getUserContributions($ucuser, array $params = array())
    {
        $paramNames = array('ucuser', 'ucuserprefix', 'ucstart', 'ucend', 
                            'uccontinue', 'ucdir', 'uclimit', 'ucnamespace', 
                            'ucshow', 'ucprop');
        foreach ($paramNames as $paramName) {
            if (array_key_exists($paramName, $params)) {
                self::getHttpClient()->setParameterPost($paramName, $params[$paramName]);
            }
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('list', 'usercontribs')
                             ->setParameterPost('ucuser', $ucuser);
        $response = $this->_request('POST', 'json');
        return $response;
    }
    
    /**
     * Return recent changes to the wiki.
     * 
     * @link http://www.mediawiki.org/wiki/API:Recentchanges
     * @param array $params
     * @return array
     */
    public function getRecentChanges(array $params = array())
    {
        $paramNames = array('rcstart', 'rcend', 'rcdir', 'rclimit', 
                            'rcnamespace', 'rcuser', 'rcexcludeuser', 'rctype', 
                            'rcshow', 'rcprop');
        foreach ($paramNames as $paramName) {
            if (array_key_exists($paramName, $params)) {
                self::getHttpClient()->setParameterPost($paramName, $params[$paramName]);
            }
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('list', 'recentchanges');
        $response = $this->_request('POST', 'json');
        return $response;
    }
    
    /**
     * Return revisions for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Properties#revisions_.2F_rv
     * @param string $titles
     * @param array $params
     * @return array
     */
    public function getRevisions($titles, array $params = array())
    {
        $paramNames = array('rvprop', 'rvcontinue', 'rvlimit', 'rvstartid', 
                            'rvendid', 'rvstart', 'rvend', 'rvdir', 'rvuser', 
                            'rvexcludeuser', 'rvexpandtemplates', 
                            'rvgeneratexml', 'rvsection', 'rvtoken', 'rvdiffto', 
                            'rvdifftotext');
        foreach ($paramNames as $paramName) {
            if (array_key_exists($paramName, $params)) {
                self::getHttpClient()->setParameterPost($paramName, $params[$paramName]);
            }
        }
        
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('prop', 'revisions')
                             ->setParameterPost('titles', $titles);
        $response = $this->_request('POST', 'json');
        return $response;
    }
    
    /**
    * Return the protection status of the specified page.
    * 
    * @link http://www.mediawiki.org/wiki/API:Properties#info_.2F_in
    * @param string $title
    * @return array
    */
    public function getPageProtections($title)
    {
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('prop', 'info')
                             ->setParameterPost('inprop', 'protection')
                             ->setParameterPost('titles', $title);
        $response = $this->_request('POST', 'json');

        $page = current($response['query']['pages']);
        return $page['protection'];
    }
    
    /**
     * Make a request, check for errors, and return parsed response.
     * 
     * @param string $format The expected format.
     * @param string $method POST or GET
     * @return array|SimpleXMLElement
     */
    protected function _request($method, $format)
    {
        // Check for valid request method.
        if (!in_array($method, array('POST', 'GET'))) {
            throw new Scripto_Service_Exception('Invalid method.');
        }
        
        // Get the response body and reset the request.
        $body = self::getHttpClient()->request($method)->getBody();
        self::getHttpClient()->resetParameters();
        
        // Parse the response body, throwing errors when encountered.
        switch ($format) {
            case 'json':
                $response = json_decode($body, true);
                if (isset($response['error'])) {
                    throw new Scripto_Service_Exception($response['error']['info']);
                }
                return $response;
            case 'xml':
                $response = new SimpleXMLElement($body);
                if (isset($response->error)) {
                    throw new Scripto_Service_Exception($response->error['info']);
                }
                return $response;
            default:
                throw new Scripto_Service_Exception('Cannot parse provided response format.');
        }
    }
}
