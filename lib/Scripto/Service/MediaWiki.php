<?php
/**
 * @copyright © 2010, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Zend/Service/Abstract.php';
require_once 'Zend/Http/Cookie.php';
require_once 'Scripto/Service/Exception.php';


class Scripto_Service_MediaWiki extends Zend_Service_Abstract
{
    const LOGIN_ERROR_WRONGPASS = 'WrongPass';
    const LOGIN_ERROR_EMPTYPASS = 'EmptyPass';
    const LOGIN_ERROR_NOTEXISTS = 'NotExists';
    const LOGIN_ERROR_NEEDTOKEN = 'NeedToken';
    const LOGIN_ERROR_NONAME    = 'NoName';
    const LOGIN_SUCCESS         = 'Success';
    
    private $_passCookies;
    private $_cookieNames;
    
    /**
     * @param string $apiUrl
     * @param string $dbName The name of the MediaWiki database.
     * @param bool $passCookies Pass cookies to the web browser.
     */
    public function __construct($apiUrl, $dbName, $passCookies = true)
    {
        $this->_passCookies = (bool) $passCookies;
        
        self::getHttpClient()->setUri($apiUrl)
                             ->setConfig(array('keepalive' => true))
                             ->setCookieJar();
        
        if ($this->_passCookies) {
            // Set the cookie names with the namespace prefix.
            $this->_cookieNames = array("{$dbName}_session", 
                                        "{$dbName}UserID", 
                                        "{$dbName}UserName", 
                                        "{$dbName}Token");
            
            // Get the MediaWiki authentication cookies from the browser and add 
            // them to the HTTP client cookie jar.
            foreach ($this->_cookieNames as $name) {
                if (array_key_exists($name, $_COOKIE)) {
                    $cookie = new Zend_Http_Cookie($name, $_COOKIE[$name], 
                                                   self::getHttpClient()->getUri()->getHost());
                    self::getHttpClient()->getCookieJar()->addCookie($cookie);
                }
            }
        }
    }
    
    /**
     * Log into MediaWiki to access protected API actions.
     * 
     * @param string $username The user's username.
     * @param string $password The user's password.
     */
    public function login($username, $password)
    {
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'login')
                             ->setParameterPost('lgname', $username)
                             ->setParameterPost('lgpassword', $password);
        
        $response = self::getHttpClient()->request('POST');
        self::getHttpClient()->resetParameters();
        
        $xml = new SimpleXMLElement($response->getBody());
        $loginResult = (string) $xml->login['result'];
        
        // Confirm the login token. See: http://www.mediawiki.org/wiki/API:Login#Confirm_token
        if (self::LOGIN_ERROR_NEEDTOKEN == $loginResult) {
            self::getHttpClient()->setParameterPost('format', 'xml')
                                 ->setParameterPost('action', 'login')
                                 ->setParameterPost('lgname', $username)
                                 ->setParameterPost('lgpassword', $password)
                                 ->setParameterPost('lgtoken', (string) $xml->login['token']);
            
            $response = self::getHttpClient()->request('POST');
            self::getHttpClient()->resetParameters();
            
            $xml = new SimpleXMLElement($response->getBody());
            $loginResult = (string) $xml->login['result'];
        }
        
        switch ($loginResult) {
            case self::LOGIN_SUCCESS:
                if ($this->_passCookies) {
                    // Persist MediaWiki authentication cookies in the browser.
                    foreach (self::getHttpClient()->getCookieJar()->getAllCookies() as $cookie) {
                        setcookie($cookie->getName(), 
                                  $cookie->getValue(), 
                                  $cookie->getExpiryTime());
                    }
                }
                break;
            case self::LOGIN_ERROR_WRONGPASS:
                throw new Scripto_Service_Exception('Password incorrect.');
            case self::LOGIN_ERROR_EMPTYPASS:
                throw new Scripto_Service_Exception('Password is empty.');
            case self::LOGIN_ERROR_NOTEXISTS:
                throw new Scripto_Service_Exception('Username not found.');
            case self::LOGIN_ERROR_NONAME:
                throw new Scripto_Service_Exception('Username is empty.');
            default:
                throw new Scripto_Service_Exception("Unknown login error: '$loginResult'");
        }
    }
    
    /**
     * Log out of MediaWiki.
     */
    public function logout()
    {
        // Reset the cookie jar.
        self::getHttpClient()->getCookieJar()->reset();
        
        if ($this->_passCookies) {
            // Delete the MediaWiki authentication cookies from the browser.
            foreach ($this->_cookieNames as $name) {
                if (array_key_exists($name, $_COOKIE)) {
                    setcookie($name, false);
                }
            }
        }
    }
    
    /**
     * Get the MediaWiki page wikitext for a specified title.
     * 
     * @param string $title The title of the page.
     * @return string The wikitext of the page.
     */
    public function getPageWikitext($title)
    {
        // Export page. See: http://www.mediawiki.org/wiki/API:Query#Exporting_pages
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('titles', $title)
                             ->setParameterPost('export', true)
                             ->setParameterPost('exportnowrap', true);
        
        $response = self::getHttpClient()->request('POST')->getBody();
        self::getHttpClient()->resetParameters();
        
        // Extract the text.
        $xml = new SimpleXMLElement($response);
        $text = null;
        if (isset($xml->page->revision->text)) {
            $text = (string) $xml->page->revision->text;
        }
        
        return $text;
    }
    
    /**
     * Get the MediaWiki page HTML for a specified title.
     * 
     * @param string $title The title of the page.
     * @return string The HTML of the page.
     */
    public function getPageHtml($title)
    {
        // Parse page. See: http://www.mediawiki.org/wiki/API:Parsing_wikitext#parse
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'parse')
        // To exclude [edit] links in the parsed wikitext, we must use the 
        // following hack. See: http://lists.wikimedia.org/pipermail/mediawiki-api/2010-April/001694.html
                             ->setParameterPost('text', '__NOEDITSECTION__{{:' . $title . '}}');
        
        $response = self::getHttpClient()->request('POST')->getBody();
        self::getHttpClient()->resetParameters();
        
        // Return the text only if the document already exists. Otherwise, the 
        // returned HTML is a link to the document's MediaWiki edit page. The 
        // only indicator I found in the response XML is the "exists" attribute 
        // in the templates node; but this may not be adequate.
        $xml = new SimpleXMLElement($response);
        $text = null;
        if (isset($xml->parse->templates->tl['exists'])) {
            $text = (string) $xml->parse->text;
        }
        
        return $text;
    }
    
    /**
     * Get an HTML preview of the provided wikitext.
     * 
     * @param string $wikitext The wikitext.
     * @return string The wikitext parsed as HTML.
     */
    public function getPreview($wikitext)
    {
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'parse')
                             ->setParameterPost('text', '__NOEDITSECTION__' . $wikitext);
        
        $response = self::getHttpClient()->request('POST')->getBody();
        self::getHttpClient()->resetParameters();
        
        $xml = new SimpleXMLElement($response);
        return (string) $xml->parse->text;
    }
    
    /**
     * Get the necessary credentials to edit the current MediaWiki page.
     * 
     * @return array|null An array containing the edittoken and basetimestamp
     */
    public function getEditCredentials($title)
    {
        // Get credentials. See: http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
        self::getHttpClient()->setParameterPost('format', 'xml')
                             ->setParameterPost('action', 'query')
                             ->setParameterPost('prop', 'info|revisions')
                             ->setParameterPost('intoken', 'edit')
                             ->setParameterPost('titles', $title);
        
        $response = self::getHttpClient()->request('POST')->getBody();
        self::getHttpClient()->resetParameters();
        
        // Extract the edittoken.
        $xml = new SimpleXMLElement($response);
        $edittoken = (string) $xml->query->pages->page['edittoken'];
        
        // Return NULL if edittoken doesn't exist.
        if (!$edittoken) {
            return null;
        }
        
        // Extract the timestamp of the last revision to protect against edit conflicts.
        $basetimestamp = null;
        if (isset($xml->query->pages->page->revisions)) {
            $basetimestamp = (string) $xml->query->pages->page->revisions->rev['timestamp'];
        }
        
        return array('edittoken' => $edittoken, 'basetimestamp' => $basetimestamp);
    }
    
    /**
     * Edit the MediaWiki page for the current document page.
     * 
     * @param string $title The title of the page to edit
     * @param string $text The wikitext of the page
     */
    public function editPage($title, $text)
    {
        $credentials = $this->getEditCredentials($title);
        
        // Edit. See: http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
        self::getHttpClient()->setParameterPost('action', 'edit')
                             ->setParameterPost('title', $title)
                             ->setParameterPost('text', $text)
                             ->setParameterPost('token', $credentials['edittoken'])
                             ->setParameterPost('basetimestamp', $credentials['basetimestamp']);
        
        $response = self::getHttpClient()->request('POST');
        self::getHttpClient()->resetParameters();
    }
}