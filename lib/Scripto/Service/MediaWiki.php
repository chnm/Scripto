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
     * @var array Valid MediaWiki API actions and their valid parameters.
     */
    protected $_actions = array(
        'parse' => array(
            'text', 'title', 'page', 'prop', 'pst', 'uselang'
        ), 
        'edit' => array(
            'title', 'section', 'text', 'token', 'summary', 'minor', 'notminor', 
            'bot', 'basetimestamp', 'starttimestamp', 'recreate', 'createonly', 
            'nocreate', 'watchlist', 'md5', 'captchaid', 'captchaword', 'undo', 
            'undoafter'
        ), 
        'protect' => array(
            'title', 'token', 'protections', 'expiry', 'reason', 'cascade'
        ), 
        'query' => array(
            'titles', 
            // submodules
            'meta', 'prop', 'list', 
            // meta submodule
            'siprop', 'sifilteriw', 'sishowalldb', 'sinumberingroup',
            'uiprop', 
            // prop submodule
            'inprop', 'intoken', 'indexpageids', 'incontinue', 
            'rvprop', 'rvcontinue', 'rvlimit', 'rvstartid', 'rvendid', 
            'rvstart', 'rvend', 'rvdir', 'rvuser', 'rvexcludeuser', 
            'rvexpandtemplates', 'rvgeneratexml', 'rvsection', 'rvtoken', 
            'rvdiffto', 'rvdifftotext', 
            // list submodule
            'ucprop', 'ucuser', 'ucuserprefix', 'ucstart', 'ucend', 
            'uccontinue', 'ucdir', 'uclimit', 'ucnamespace', 'ucshow', 
            'rcprop', 'rcstart', 'rcend', 'rcdir', 'rclimit', 'rcnamespace', 
            'rcuser', 'rcexcludeuser', 'rctype', 'rcshow', 
        ), 
        'login' => array(
            'lgname', 'lgpassword', 'lgtoken'
        ), 
        'logout' => array()
    );
    
    /**
     * Constructs the MediaWiki API client.
     * 
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
     * Gets information about the currently logged-in user.
     * 
     * @link http://www.mediawiki.org/wiki/API:Meta#userinfo_.2F_ui
     * @param string $uiprop
     * @return array
     */
    public function getUserInfo($uiprop = '')
    {
        $params = array('meta'   => 'userinfo', 
                        'uiprop' => $uiprop);
        return $this->query($params);
    }
    
    /**
     * Gets overall site information.
     * 
     * @link http://www.mediawiki.org/wiki/API:Meta#siteinfo_.2F_si
     * @param string $siprop
     * @return array
     */
    public function getSiteInfo($siprop = 'general')
    {
        $params = array('meta'   => 'siteinfo', 
                        'siprop' => $siprop);
        return $this->query($params);
    }
    
    /**
     * Gets a list of contributions made by a given user.
     * 
     * @link http://www.mediawiki.org/wiki/API:Usercontribs
     * @param string $ucuser
     * @param array $params
     * @return array
     */
    public function getUserContributions($ucuser, array $params = array())
    {
        $params['ucuser'] = $ucuser;
        $params['list'] = 'usercontribs';
        return $this->query($params);
    }
    
    /**
     * Gets all recent changes to the wiki.
     * 
     * @link http://www.mediawiki.org/wiki/API:Recentchanges
     * @param array $params
     * @return array
     */
    public function getRecentChanges(array $params = array())
    {
        $params['list'] = 'recentchanges';
        return $this->query($params);
    }
    
    /**
     * Gets basic page information.
     * 
     * @link http://www.mediawiki.org/wiki/API:Properties#info_.2F_in
     * @param string $titles
     * @param array $params
     * @return array
     */
    public function getInfo($titles, array $params = array())
    {
        $params['titles'] = $titles;
        $params['prop'] = 'info';
        return $this->query($params);
    }
    
    /**
     * Returns revisions for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Properties#revisions_.2F_rv
     * @param string $titles
     * @param array $params
     * @return array
     */
    public function getRevisions($titles, array $params = array())
    {
        $params['titles'] = $titles;
        $params['prop'] = 'revisions';
        return $this->query($params);
    }
    
    /**
     * Gets the edit token for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Edit#Token
     * @param string $title
     * @return string
     */
    public function getEditToken($title)
    {
        $response = $this->getInfo($title, array('intoken' => 'edit'));
        $page = current($response['query']['pages']);
        
        $edittoken = null;
        if (isset($page['edittoken'])) {
            $edittoken = $page['edittoken'];
        }
        return $edittoken;
    }
    
    /**
     * Gets the protect token for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Protect#Token
     * @param string $title
     * @return string
     */
    public function getProtectToken($title)
    {
        $response = $this->getInfo($title, array('intoken' => 'protect'));
        $page = current($response['query']['pages']);
        
        $protecttoken = null;
        if (isset($page['protecttoken'])) {
            $protecttoken = $page['protecttoken'];
        }
        return $protecttoken;
    }
    
    /**
     * Gets the protections for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Properties#info_.2F_in
     * @param string $title
     * @return array
     */
    public function getPageProtections($title)
    {
        $response = $this->getInfo($title, array('inprop' => 'protection'));
        $page = current($response['query']['pages']);
        return $page['protection'];
    }
    
    /**
     * Gets the wikitext of the latest revision of a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Properties#revisions_.2F_rv
     * @param string $title
     * @return string|null
     */
    public function getLatestRevisionWikitext($title)
    {
        $response = $this->getRevisions($title, array('rvprop'  => 'content', 
                                                      'rvlimit' => '1'));
        $page = current($response['query']['pages']);
        
        // Return the wikitext only if the page already exists.
        $wikitext = null;
        if (isset($page['revisions'][0]['*'])) {
            $wikitext = $page['revisions'][0]['*'];
        }
        return $wikitext;
    }
    
    /**
     * Gets the HTML of the latest revision of a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Parsing_wikitext#parse
     * @param string $title
     * @return string|null
     */
    public function getLatestRevisionHtml($title)
    {
        // To exclude [edit] links in the parsed wikitext, we must use the 
        // following hack.
        $response = $this->parse(array('text' => '__NOEDITSECTION__{{:' . $title . '}}'));
        
        // Return the text only if the page already exists. Otherwise, the 
        // returned HTML is a link to the document's MediaWiki edit page. The 
        // only indicator I found in the response XML is the "exists" attribute 
        // in the templates node; but this may not be adequate.
        $html = null;
        if (isset($response['parse']['templates'][0]['exists'])) {
            $html = $response['parse']['text']['*'];
        }
        return $html;
    }
    
    /**
     * Get the HTML preview of the given text.
     * 
     * @link http://www.mediawiki.org/wiki/API:Parsing_wikitext#parse
     * @param string $text
     * @return string
     */
    public function getPreview($text)
    {
        $response = $this->parse(array('text' => '__NOEDITSECTION__' . $text));
        return $parse['parse']['text']['*'];
    }
    
    /**
     * Returns whether a given page is created.
     * 
     * @link http://www.mediawiki.org/wiki/API:Query#Missing_and_invalid_titles
     * @param string $title
     * @return bool
     */
    public function pageCreated($title)
    {
        $response = $this->query(array('titles' => $title));
        $page = current($response['query']['pages']);
        if (isset($page['missing']) || isset($page['invalid'])) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns parsed wikitext.
     * 
     * @link http://www.mediawiki.org/wiki/API:Parsing_wikitext#parse
     * @param array $params
     * @return array
     */
    public function parse(array $params = array())
    {
        return $this->_request('parse', $params);
    }
    
    /**
     * Returns data.
     * 
     * @link http://www.mediawiki.org/wiki/API:Query
     * @param array $params
     * @return array
     */
    public function query(array $params = array())
    {
        return $this->_request('query', $params);
    }
    
    /**
     * Applies protections to a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @param string $protections
     * @param string|null $protecttokens
     * @param array $params
     * @return array
     */
    public function protect($title, 
                            $protections, 
                            $protecttoken = null, 
                            array $params = array())
    {
        // Get the protect token if not passed.
        if (is_null($protecttoken)) {
            $protecttoken = $this->getProtectToken($title);
        }
        
        // Apply protections.
        $params['title']       = $title;
        $params['protections'] = $protections;
        $params['token']       = $protecttoken;
        
        return $this->_request('protect', $params);
    }
    
    /**
     * Create or edit a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Edit
     * @param string $title
     * @param string $text
     * @param string|null $edittoken
     * @param array $params
     * @return array
     */
    public function edit($title, 
                         $text, 
                         $edittoken = null, 
                         array $params = array())
    {
        // Get the edit token if not passed.
        if (is_null($edittoken)) {
            $edittoken = $this->getEditToken($title);
        }
        
        // Protect against edit conflicts by getting the timestamp of the last 
        // revision.
        $response = $this->getRevisions($title);
        $page = current($response['query']['pages']);
        
        $basetimestamp = null;
        if (isset($page['revisions'])) {
            $basetimestamp = $page['revisions'][0]['timestamp'];
        }
        
        // Edit the page.
        $params['title']         = $title;
        $params['text']          = $text;
        $params['token']         = $edittoken;
        $params['basetimestamp'] = $basetimestamp;
        
        return $this->_request('edit', $params);
    }
    
    /**
     * Login to MediaWiki.
     * 
     * @link http://www.mediawiki.org/wiki/API:Login
     * @param string $lgname
     * @param string $lgpassword
     */
    public function login($lgname, $lgpassword)
    {
        // Log in or get the login token.
        $params = array('lgname' => $lgname, 'lgpassword' => $lgpassword);
        $response = $this->_request('login', $params);
        
        // Confirm the login token.
        if ('NeedToken' == $response['login']['result']) {
            $params['lgtoken'] = $response['login']['token'];
            $response = $this->_request('login', $params);
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
        throw new Scripto_Service_Exception('Unknown login error: ' . $response['login']['result']);
    }
    
    /**
     * Logout of MediaWiki.
     * 
     * @link http://www.mediawiki.org/wiki/API:Logout
     */
    public function logout()
    {
        // Log out.
        $this->_request('logout');
        
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
     * Makes a MediaWiki API request and returns the response.
     * 
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function _request($action, array $params = array())
    {
        //echo "<pre>$action:\n";print_r($params);echo '</pre>';
        
        // Check if this action is a valid MediaWiki API action.
        if (!array_key_exists($action, $this->_actions)) {
            throw new Scripto_Service_Exception('Invalid MediaWiki API action.');
        }
        
        // Set valid parameters for this action.
        foreach ($params as $paramName => $paramValue) {
            if (in_array($paramName, $this->_actions[$action])) {
                self::getHttpClient()->setParameterPost($paramName, $paramValue);
            }
        }
        
        // Set default parameters.
        self::getHttpClient()->setParameterPost('format', 'json')
                             ->setParameterPost('action', $action);
        
        // Get the response body and reset the request.
        $body = self::getHttpClient()->request('POST')->getBody();
        self::getHttpClient()->resetParameters();
        
        // Parse the response body, throwing errors when encountered.
        $response = json_decode($body, true);
        if (isset($response['error'])) {
            throw new Scripto_Service_Exception($response['error']['info']);
        }
        return $response;
    }
}
