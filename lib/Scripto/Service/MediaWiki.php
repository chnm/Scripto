<?php
/**
 * @package Scripto
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @see Zend_Service_Abstract
 */
require_once 'Zend/Service/Abstract.php';

/**
 * @see Scripto_Service_Exception
 */
require_once 'Scripto/Service/Exception.php';

/**
 * MediaWIki API client.
 * 
 * @package Scripto
 */
class Scripto_Service_MediaWiki extends Zend_Service_Abstract
{
    /**
     * The cookie namespace, used to namespace Scripto/MediaWiki cookies when
     * passed to the browser.
     */
    const COOKIE_NS = 'scripto_';
    
    /**
     * @var string The cookie prefix set by MediaWiki.
     */
    protected $_cookiePrefix;
    
    /**
     * @var bool Pass Scripto cookies to the web browser.
     */
    protected $_passCookies;
    
    /**
     * @var array Scripto/MediaWiki cookie name suffixes.
     */
    protected $_cookieSuffixes = array('_session', 'UserID', 'UserName', 'Token');
    
    /**
     * @var array Valid MediaWiki API actions and their valid parameters. This 
     * whitelist is used to prohibit invalid actions and parameters from being 
     * set to API requests.
     * @todo Remember to add relevant actions and parameters when adding new API 
     * functionality.
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
        'watch' => array(
            'title', 'unwatch', 'token'
        ), 
        'query' => array(
            // title specifications
            'titles', 'revids', 'pageids', 
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
            'wlprop', 'wlstart', 'wlend', 'wldir', 'wllimit', 'wlnamespace', 
            'wluser', 'wlexcludeuser', 'wlowner', 'wltoken', 'wlallrev', 
            'wlshow', 
            'aplimit', 'apminsize', 'apmaxsize', 'apprefix', 'apfrom', 
            'apnamespace', 'apfilterredir', 'apfilterlanglinks', 'apprtype', 
            'apprlevel', 'apdir', 
        ), 
        'login' => array(
            'lgname', 'lgpassword', 'lgtoken'
        ), 
        'logout' => array()
    );
    
    /**
     * Constructs the MediaWiki API client.
     * 
     * @link http://www.mediawiki.org/wiki/API:Main_page
     * @param string $apiUrl The URL to the MediaWiki API.
     * @param bool $passCookies Pass cookies to the web browser.
     * @param string $cookiePrefix
     */
    public function __construct($apiUrl, $passCookies = true, $cookiePrefix = null)
    {
        $this->_passCookies = (bool) $passCookies;

        if (null !== $cookiePrefix) {
            $this->_cookiePrefix = $cookiePrefix;
        } elseif (isset($_COOKIE[self::COOKIE_NS . 'cookieprefix'])) {
            // Set the cookie prefix that was set by MediaWiki during login.
            $this->_cookiePrefix = $_COOKIE[self::COOKIE_NS . 'cookieprefix'];
        }
        
        // Set the HTTP client for the MediaWiki API .
        self::getHttpClient()->setUri($apiUrl)
                             ->setConfig(array('keepalive' => true))
                             ->setCookieJar();
        
        // Add X-Forwarded-For header if applicable.
        if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['SERVER_ADDR'])) {
            self::getHttpClient()->setHeaders('X-Forwarded-For', 
                                              $_SERVER['REMOTE_ADDR'] . ', ' . $_SERVER['SERVER_ADDR']);
        }
        
        // If MediaWiki API authentication cookies are being passed and the 
        // MediaWiki cookieprefix is set, get the cookies from the browser and 
        // add them to the HTTP client cookie jar. Doing so maintains state 
        // between browser requests.
        if ($this->_passCookies && $this->_cookiePrefix) {
            require_once 'Zend/Http/Cookie.php';
            foreach ($this->_cookieSuffixes as $cookieSuffix) {
                $cookieName = self::COOKIE_NS . $this->_cookiePrefix . $cookieSuffix;
                if (array_key_exists($cookieName, $_COOKIE)) {
                    $cookie = new Zend_Http_Cookie($this->_cookiePrefix . $cookieSuffix, 
                                                   $_COOKIE[$cookieName], 
                                                   self::getHttpClient()->getUri()->getHost());
                    self::getHttpClient()->getCookieJar()->addCookie($cookie);
                }
            }
        }
    }
    
    /**
     * Gets information about the current user.
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
     * Gets a list of pages on the current user's watchlist.
     * 
     * @link http://www.mediawiki.org/wiki/API:Watchlist
     * @param array $params
     * @return array
     */
    public function getWatchlist(array $params = array())
    {
        $params['list'] = 'watchlist';
        return $this->query($params);
    }
    
    /**
     * Gets a list of pages.
     * 
     * @link http://www.mediawiki.org/wiki/API:Allpages
     * @param array $params
     * @return array
     */
    public function getAllPages(array $params = array())
    {
        $params['list'] = 'allpages';
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
     * Gets revisions for a given page.
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
     * Gets the HTML of a specified revision of a given page.
     * 
     * @param int $revisionId
     * @return string
     */
    public function getRevisionHtml($revisionId)
    {
        // Get the revision wikitext.
        $response = $this->getRevisions(null, array('revids' => $revisionId, 
                                                    'rvprop' => 'content'));
        $page = current($response['query']['pages']);
        
        // Parse the wikitext into HTML.
        $response = $this->parse(
            array('text' => '__NOEDITSECTION__' . $page['revisions'][0]['*'])
        );
        return $response['parse']['text']['*'];
    }
    
    /**
     * Gets the difference between two revisions.
     * 
     * @param int $from The revision ID to diff.
     * @param int|string $to The revision to diff to: use the revision ID, 
     * prev, next, or cur.
     * @return string The API returns preformatted table rows without a wrapping 
     * <table>. Presumably this is so implementers can wrap a custom <table>.
     */
    public function getRevisionDiff($fromRevisionId, $toRevisionId = 'prev')
    {
        $response = $this->getRevisions(null, array('revids'   => $fromRevisionId, 
                                                    'rvdiffto' => $toRevisionId));
        $page = current($response['query']['pages']);
        return $page['revisions'][0]['diff']['*'];
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
     * Gets the watch token for a given page.
     * 
     * @link http://www.mediawiki.org/wiki/API:Watch#Token
     * @param string $title
     * @return string
     */
    public function getWatchToken($title)
    {
        $response = $this->getInfo($title, array('intoken' => 'watch'));
        $page = current($response['query']['pages']);
        
        $watchtoken = null;
        if (isset($page['watchtoken'])) {
            $watchtoken = $page['watchtoken'];
        }
        return $watchtoken;
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
        return $response['parse']['text']['*'];
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
     * Watch or unwatch pages.
     * 
     * @link http://www.mediawiki.org/wiki/API:Watch
     * @param string $title
     * @param array $params
     * @return array
     */
    public function watch($title, $watchtoken = null, array $params = array())
    {
        // Get the watch token if not passed.
        if (is_null($watchtoken)) {
            $watchtoken = $this->getWatchToken($title);
        }
        $params['title'] = $title;
        $params['token'] = $watchtoken;
        return $this->_request('watch', $params);
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
     * @link http://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_editing_of_all_pages
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
                $cookiePrefix = isset($response['login']['cookieprefix'])
                    ? $response['login']['cookieprefix']
                    : $this->_cookiePrefix;
                // Persist the MediaWiki cookie prefix in the browser. Set to 
                // expire in 30 days, the same as MediaWiki cookies.
                setcookie(self::COOKIE_NS . 'cookieprefix',
                          $cookiePrefix,
                          time() + 60 * 60 * 24 * 30, 
                          '/');
                
                // Persist MediaWiki authentication cookies in the browser.
                foreach (self::getHttpClient()->getCookieJar()->getAllCookies() as $cookie) {
                    setcookie(self::COOKIE_NS . $this->cookiePrefix . $cookie->getName(),
                              $cookie->getValue(), 
                              $cookie->getExpiryTime(), 
                              '/');
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
        
        if ($this->_passCookies && $this->_cookiePrefix) {
            // Delete the MediaWiki authentication cookies from the browser.
            setcookie(self::COOKIE_NS . 'cookieprefix', false, 0, '/');
            foreach ($this->_cookieSuffixes as $cookieSuffix) {
                $cookieName = self::COOKIE_NS . $this->_cookiePrefix . $cookieSuffix;
                if (array_key_exists($cookieName, $_COOKIE)) {
                    setcookie($cookieName, false, 0, '/');
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
    
    /**
     * Determine whether the provided MediaWiki API URL is valid.
     * 
     * @param string $apiUrl
     * @return bool
     */
    static public function isValidApiUrl($apiUrl)
    {
        // Check for valid API URL string.
        if (!Zend_Uri::check($apiUrl) || !preg_match('#/api\.php$#', $apiUrl)) {
            return false;
        }
        
        try {
            // Ping the API endpoint for a valid response.
            $body = self::getHttpClient()->setUri($apiUrl)
                                         ->setParameterPost('action', 'query')
                                         ->setParameterPost('meta', 'siteinfo')
                                         ->setParameterPost('format', 'json')
                                         ->request('POST')->getBody();
        // Prevent "Unable to Connect" errors.
        } catch (Zend_Http_Client_Exception $e) {
            return false;
        }
        self::getHttpClient()->resetParameters(true);
        
        $response = json_decode($body, true);
        if (!is_array($response) || !isset($response['query']['general'])) {
            return false;
        }
        
        return true;
    }
}
