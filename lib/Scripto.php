<?php
/**
 * @package Scripto
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @see Scripto_Exception
 */
require_once 'Scripto/Exception.php';

/**
 * @see Scripto_Document
 */
require_once 'Scripto/Document.php';

/**
 * @see Scripto_Service_MediaWiki
 */
require_once 'Scripto/Service/MediaWiki.php';

/**
 * Represents a Scripto application. Serves as a connector object between the 
 * external system API and MediaWiki API.
 * 
 * @package Scripto
 */
class Scripto
{
    /**
     * This Scripto version.
     */
    const VERSION = '1.2.0';
    
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
     * @var array Cached information about the current user.
     */
    protected $_userInfo;
    
    /**
     * Construct the Scripto object.
     * 
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param array|Scripto_Service_MediaWiki $mediawiki If an array:
     * <ul>
     *     <li>$mediawiki['api_url']: required; the MediaWiki API URL</li>
     *     <li>$mediawiki['pass_cookies']: optional pass cookies to the web 
     *     <li>$mediawiki['cookie_prefix']: optional; set the cookie prefix
     *     browser via API client</li>
     * </ul>
     */
    public function __construct(Scripto_Adapter_Interface $adapter, $mediawiki)
    {
        // Set the adapter.
        $this->_adapter = $adapter;
        
        // Set the MediaWiki service.
        if ($mediawiki instanceof Scripto_Service_MediaWiki) {
            $this->_mediawiki = $mediawiki;
        } else if (is_array($mediawiki) && array_key_exists('api_url', $mediawiki)) {
            if (!isset($mediawiki['pass_cookies'])) {
                $mediawiki['pass_cookies'] = true;
            }
            if (!isset($mediawiki['cookie_prefix'])) {
                $mediawiki['cookie_prefix'] = null;
            }
            
            $this->_mediawiki = new Scripto_Service_MediaWiki($mediawiki['api_url'], 
                                                              (bool) $mediawiki['pass_cookies'],
                                                              $mediawiki['cookie_prefix']);
        } else {
            throw new Scripto_Exception('The provided mediawiki parameter is invalid.');
        }
        
        // Set the user information.
        $this->setUserInfo();
    }
    
    /**
     * Provide a transparent interface for calling custom adapter methods.
     * 
     * This makes it possible to call custom adapter methods (those not required 
     * by Scripto_Adapter_Interface) directly from the Scripto object.
     * 
     * @see Scripto_Adapter_Interface
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (!method_exists($this->_adapter, $name)) {
            require_once 'Scripto/Adapter/Exception.php';
            throw new Scripto_Adapter_Exception('The provided adapter method "' . $name . '" does not exist.');
        }
        return call_user_func_array(array($this->_adapter, $name), $args);
    }
    
    /**
     * Check whether the specified document exists in the external system.
     * 
     * @uses Scripto_Adapter_Interface::documentExists()
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
     * @see Scripto_Document
     * @param string|int $id The unique document identifier.
     * @return Scripto_Document
     */
    public function getDocument($id)
    {
        return new Scripto_Document($id, $this->_adapter, $this->_mediawiki);
    }
    
    /**
     * Login via the MediaWiki service.
     * 
     * It is possible to restrict account creation in MediaWiki.
     * @link http://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_account_creation
     * 
     * @uses Scripto_Service_MediaWiki::login()
     * @param string $username The MediaWiki user's username.
     * @param string $password The MediaWiki user's password.
     */
    public function login($username, $password)
    {
        $this->_mediawiki->login($username, $password);
        $this->setUserInfo();
    }
    
    /**
     * Logout via the MediaWiki service.
     * 
     * @uses Scripto_Service_MediaWiki::logout()
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
     * Determine if the current user can protect MediaWiki pages.
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
     * 
     * @uses Scripto_Service_MediaWiki::getUserInfo()
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
     * @uses Scripto_Service_MediaWiki::getUserContributions()
     * @param int $limit The number of document pages to return.
     * @return array
     */
    public function getUserDocumentPages($limit = 10)
    {
        $limit = (int) $limit;
        $userDocumentPages = array();
        $documentTitles = array();
        $start = null;
        
        // Namespaces to get: ns_index => ns_name
        // See http://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces
        $namespaces = array('0' => 'Main', '1' => 'Talk');
        
        do {
            $response = $this->_mediawiki->getUserContributions(
                $this->_userInfo['query']['userinfo']['name'], 
                array('ucstart' => $start, 
                      'ucnamespace' => implode('|', array_keys($namespaces)), 
                      'uclimit' => 100)
            );
            foreach ($response['query']['usercontribs'] as $value) {
                
                // Filter out duplicate pages.
                if (array_key_exists($value['pageid'], $userDocumentPages)) {
                    continue;
                }
                
                // Extract the title, removing the namespace if any.
                $title = preg_replace('/^(.+:)?(.+)$/', '$2', $value['title']);
                
                // Preempt further processing on contributions with an invalid 
                // prefix.
                if (Scripto_Document::BASE_TITLE_PREFIX != $title[0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $documentIds = Scripto_Document::decodeBaseTitle($title);
                
                // Filter out contributions that are not valid document pages.
                if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                    continue;
                }
                
                // Set the document title and document page name. Reduce calls 
                // to the adapter by caching each document title, and checking 
                // if they exist.
                if (array_key_exists($documentIds[0], $documentTitles)) {
                    $documentTitle = $documentTitles[$documentIds[0]];
                } else {
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                }
                
                // Duplicate pages have already been filtered out, so there is 
                // no need to cache document page names.
                $documentPageName = $this->_adapter->getDocumentPageName($documentIds[0], $documentIds[1]);
                
                // Build the user document pages, newest properties first.
                $userDocumentPages[$value['pageid']] = array(
                    'revision_id'        => $value['revid'], 
                    'namespace_index'    => $value['ns'], 
                    'namespace_name'     => $namespaces[$value['ns']], 
                    'mediawiki_title'    => $value['title'], 
                    'timestamp'          => $value['timestamp'], 
                    'comment'            => $value['comment'], 
                    'size'               => $value['size'], 
                    'document_id'        => $documentIds[0], 
                    'document_page_id'   => $documentIds[1], 
                    'document_title'     => $documentTitle, 
                    'document_page_name' => $documentPageName, 
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
     * @uses Scripto_Service_MediaWiki::getRecentChanges()
     * @param int $limit The number of recent changes to return.
     * @return array
     */
    public function getRecentChanges($limit = 10)
    {
        $start = null;
        $recentChanges = array();
        $documentTitles = array();
        $documentPageNames = array();
        
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
                
                // Set the document title and document page name. Reduce calls 
                // to the adapter by caching each document title and page name, 
                // and checking if they exist.
                $cachedDocument = array_key_exists($documentIds[0], $documentTitles);
                $cachedDocumentPage = array_key_exists($documentIds[1], $documentPageNames);
                
                // The document title and page name have been cached.
                if ($cachedDocument && $cachedDocumentPage) {
                    $documentTitle = $documentTitles[$documentIds[0]];
                    $documentPageName = $documentPageNames[$documentIds[1]];
                
                // The document title has been cached, but not the page name.
                } else if ($cachedDocument && !$cachedDocumentPage) {
                    // Filter out invalid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $documentTitles[$documentIds[0]];
                    $documentPageName = $this->_adapter->getDocumentPageName($documentIds[0], $documentIds[1]);
                    $documentPageNames[$documentIds[1]] = $documentPageName;
                
                // The document title and page name have not been cached.
                } else {
                    // Filter out invalid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                    $documentPageName = $this->_adapter->getDocumentPageName($documentIds[0], $documentIds[1]);
                    $documentPageNames[$documentIds[1]] = $documentPageName;
                }
                
                $logAction = isset($value['logaction']) ? $value['logaction']: null;
                $action = self::getChangeAction(array('comment' => $value['comment'], 
                                                      'log_action' => $logAction));
                
                $recentChanges[] = array(
                    'type'               => $value['type'], 
                    'namespace_index'    => $value['ns'], 
                    'namespace_name'     => $namespaces[$value['ns']], 
                    'mediawiki_title'    => $value['title'], 
                    'rcid'               => $value['rcid'], 
                    'page_id'            => $value['pageid'], 
                    'revision_id'        => $value['revid'], 
                    'old_revision_id'    => $value['old_revid'], 
                    'user'               => $value['user'], 
                    'old_length'         => $value['oldlen'], 
                    'new_length'         => $value['newlen'], 
                    'timestamp'          => $value['timestamp'], 
                    'comment'            => $value['comment'], 
                    'action'             => $action, 
                    'log_id'             => isset($value['logid']) ? $value['logid']: null, 
                    'log_type'           => isset($value['logtype']) ? $value['logtype']: null, 
                    'log_action'         => $logAction, 
                    'new'                => isset($value['new']) ? true: false, 
                    'minor'              => isset($value['minor']) ? true: false, 
                    'document_id'        => $documentIds[0], 
                    'document_page_id'   => $documentIds[1], 
                    'document_title'     => $documentTitle, 
                    'document_page_name' => $documentPageName, 
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
     * Get the current user's watchlist.
     * 
     * @link http://www.mediawiki.org/wiki/API:Watchlist
     * @uses Scripto_Service_MediaWiki::getWatchlist()
     * @param int $limit The number of recent changes to return.
     * @return array
     */
    public function getWatchlist($limit = 10)
    {
        $start = null;
        $watchlist = array();
        $documentTitles = array();
        $documentPageNames = array();
        
        // Namespaces to get: ns_index => ns_name
        // See http://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces
        $namespaces = array('0' => 'Main', '1' => 'Talk');
        
        do {
            $response = $this->_mediawiki->getWatchlist(
                array('wlprop'      => 'user|comment|timestamp|title|ids|sizes|flags', 
                      'wllimit'     => '100', 
                      'wlallrev'    => true, 
                      'wlnamespace' => implode('|', array_keys($namespaces)), 
                      'wlstart'     => $start)
            );
            
            foreach ($response['query']['watchlist'] as $value) {
                
                // Extract the title, removing the namespace if any.
                $title = preg_replace('/^(.+:)?(.+)$/', '$2', $value['title']);
                
                // Preempt further processing on contributions with an invalid 
                // prefix.
                if (Scripto_Document::BASE_TITLE_PREFIX != $title[0]) {
                    continue;
                }
                
                // Set the document ID and page ID.
                $documentIds = Scripto_Document::decodeBaseTitle($title);
                
                // Set the document title and document page name. Reduce calls 
                // to the adapter by caching each document title and page name, 
                // and checking if they exist.
                $cachedDocument = array_key_exists($documentIds[0], $documentTitles);
                $cachedDocumentPage = array_key_exists($documentIds[1], $documentPageNames);
                
                // The document title and page name have been cached.
                if ($cachedDocument && $cachedDocumentPage) {
                    $documentTitle = $documentTitles[$documentIds[0]];
                    $documentPageName = $documentPageNames[$documentIds[1]];
                
                // The document title has been cached, but not the page name.
                } else if ($cachedDocument && !$cachedDocumentPage) {
                    // Filter out invalid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $documentTitles[$documentIds[0]];
                    $documentPageName = $this->_adapter->getDocumentPageName($documentIds[0], $documentIds[1]);
                    $documentPageNames[$documentIds[1]] = $documentPageName;
                
                // The document title and page name have not been cached.
                } else {
                    // Filter out invalid document pages.
                    if (!$this->_adapter->documentPageExists($documentIds[0], $documentIds[1])) {
                        continue;
                    }
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                    $documentPageName = $this->_adapter->getDocumentPageName($documentIds[0], $documentIds[1]);
                    $documentPageNames[$documentIds[1]] = $documentPageName;
                }
                
                $action = self::getChangeAction(array('comment' => $value['comment'], 
                                                      'revision_id' => $value['revid']));
                
                $watchlist[] = array(
                    'namespace_index'    => $value['ns'], 
                    'namespace_name'     => $namespaces[$value['ns']], 
                    'mediawiki_title'    => $value['title'], 
                    'page_id'            => $value['pageid'], 
                    'revision_id'        => $value['revid'], 
                    'user'               => $value['user'], 
                    'old_length'         => $value['oldlen'], 
                    'new_length'         => $value['newlen'], 
                    'timestamp'          => $value['timestamp'], 
                    'comment'            => $value['comment'], 
                    'action'             => $action, 
                    'new'                => isset($value['new']) ? true: false, 
                    'minor'              => isset($value['minor']) ? true: false, 
                    'anonymous'          => isset($value['anon']) ? true: false, 
                    'document_id'        => $documentIds[0], 
                    'document_page_id'   => $documentIds[1], 
                    'document_title'     => $documentTitle, 
                    'document_page_name' => $documentPageName, 
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($watchlist)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($response['query-continue'])) {
                $start = $response['query-continue']['watchlist']['wlstart'];
            } else {
                $start = null;
            }
            
        } while ($start);
        
        return $watchlist;
    }
    
    /**
     * Get all documents from MediaWiki that have at least one page with text.
     * 
     * @uses Scripto_Service_MediaWiki::getAllPages()
     * @return array An array following this format:
     * <code>
     * array(
     *     {document ID} => array(
     *         ['mediawiki_titles'] => array(
     *             {page ID} => {mediawiki title}, 
     *             {...}
     *         ), 
     *         ['document_title'] => {document title}
     *     ), 
     *     {...}
     * )
     * </code>
     */
    public function getAllDocuments()
    {
        $from = null;
        $documentTitles = array();
        $allDocuments = array();
        do {
            $response = $this->_mediawiki->getAllPages(
                array('aplimit'   => 500, 
                      'apminsize' => 1, 
                      'apprefix'  => Scripto_Document::BASE_TITLE_PREFIX, 
                      'apfrom'    => $from)
            );
            
            foreach ($response['query']['allpages'] as $value) {
                
                // Set the document ID and page ID.
                $documentIds = Scripto_Document::decodeBaseTitle($value['title']);
                
                // Set the page and continue if the document was already set.
                if (array_key_exists($documentIds[0], $documentTitles)) {
                    $allDocuments[$documentIds[0]]['mediawiki_titles'][$documentIds[1]] = $value['title'];
                    continue;
                
                // Set the document. Before getting the title, filter out pages 
                // that are not valid documents.
                } else {
                    if (!$this->_adapter->documentExists($documentIds[0])) {
                        continue;
                    }
                    $documentTitle = $this->_adapter->getDocumentTitle($documentIds[0]);
                    $documentTitles[$documentIds[0]] = $documentTitle;
                }
                
                $allDocuments[$documentIds[0]] = array(
                    'mediawiki_titles' => array($documentIds[1] => $value['title']), 
                    'document_title'   => $documentTitle, 
                );
            }
            
            // Set the query continue, if any.
            if (isset($response['query-continue'])) {
                $from = $response['query-continue']['allpages']['apfrom'];
            } else {
                $from = null;
            }
            
        } while ($from);
        
        return $allDocuments;
    }
    
    /**
     * Get the difference between two page revisions.
     * 
     * @uses Scripto_Service_MediaWiki::getRevisionDiff()
     * @param int $fromRevisionId The revision ID from which to diff.
     * @param int|string $toRevisionId The revision to which to diff. Use the 
     * revision ID, "prev", "next", or "cur".
     * @return string An HTML table without the wrapping <table> tag containing 
     * difference markup, pre-formatted by MediaWiki. It is the responsibility 
     * of implementers to wrap the result with table tags.
     */
    public function getRevisionDiff($fromRevisionId, $toRevisionId = 'prev')
    {
        return $this->_mediawiki->getRevisionDiff($fromRevisionId, $toRevisionId);
    }
    
    /**
     * Get properties of the specified page revision.
     * 
     * @uses Scripto_Service_MediaWiki::getRevisions()
     * @param int $revisionId The ID of the rpage evision.
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
        
        $action = self::getChangeAction(array('comment' => $page['revisions'][0]['comment']));
        
        $revision = array('revision_id' => $page['revisions'][0]['revid'], 
                          'parent_id'   => $page['revisions'][0]['parentid'], 
                          'user'        => $page['revisions'][0]['user'], 
                          'timestamp'   => $page['revisions'][0]['timestamp'], 
                          'comment'     => $page['revisions'][0]['comment'], 
                          'size'        => $page['revisions'][0]['size'], 
                          'action'      => $action, 
                          'wikitext'    => $page['revisions'][0]['*'], 
                          'html'        => $response['parse']['text']['*']);
        return $revision;
    }
    
    /**
     * Infer a change action verb from hints containted in various responses.
     * 
     * @param array $hints Keyed hints from which to infer an change action:
     * <ul>
     *     <li>comment</li>
     *     <li>log_action</li>
     *     <li>revision_id</li>
     * </ul>
     * @return string
     */
    static public function getChangeAction(array $hints = array())
    {
        $action = '';
        
        // Recent changes returns log_action=protect|unprotect with no comment.
        if (array_key_exists('log_action', $hints)) {
            $logActions = array('protect' => 'protected', 'unprotect' => 'unprotected');
            if (array_key_exists($hints['log_action'], $logActions)) {
                return $logActions[$hints['log_action']];
            }
        }
        
        // Infer from comment and revision_id.
        if (array_key_exists('comment', $hints)) {
            $commentActions = array('Replaced', 'Unprotected', 'Protected', 'Created');
            $actionPattern = '/^(' . implode('|', $commentActions) . ').+$/s';
            if (preg_match($actionPattern, $hints['comment'])) {
                $action = preg_replace_callback($actionPattern, function ($matches) {
                    return strtolower($matches[1]);
                }, $hints['comment']);
            } else {
                // Watchlist returns revision_id=0 when the action is protect 
                // or unprotect.
                if (array_key_exists('revision_id', $hints) && 0 == $hints['revision_id']) {
                    $action = 'un/protected';
                } else {
                    $action = 'edited';
                }
            }
        }
        
        return $action;
    }
    
    /**
     * Determine whether the provided MediaWiki API URL is valid.
     * 
     * @uses Scripto_Service_MediaWiki::isValidApiUrl()
     * @param string $apiUrl The MediaWiki API URL to validate.
     * @return bool
     */
    static public function isValidApiUrl($apiUrl)
    {
        return Scripto_Service_MediaWiki::isValidApiUrl($apiUrl);
    }
    
    /**
     * Remove all HTML attributes from the provided markup.
     * 
     * This filter is useful after getting HTML from the MediaWiki API, which 
     * often contains MediaWiki-specific attributes that may conflict with local 
     * settings.
     * 
     * @see http://www.php.net/manual/en/domdocument.loadhtml.php#95251
     * @param string $html
     * @param array $exceptions Do not remove these attributes.
     * @return string
     */
    static public function removeHtmlAttributes($html, array $exceptions = array('href'))
    {
        // Check for an empty string.
        $html = trim($html);
        if (empty($html)) {
            return $html;
        }
        
        // Load the HTML into DOM. Must inject an XML declaration with encoding 
        // set to UTF-8 to prevent DOMDocument from munging Unicode characters.
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($doc);
        
        // Iterate over and remove attributes.
        foreach ($xpath->evaluate('//@*') as $attribute) {
            // Do not remove specified attributes.
            if (in_array($attribute->name, $exceptions)) {
                continue;
            }
            $attribute->ownerElement->removeAttributeNode($attribute);
        }
        
        return $doc->saveHTML();
    }
    
    /**
     * Remove all preprocessor limit reports from the provided markup.
     * 
     * This filter is useful after getting HTML from the MediaWiki API, which 
     * always contains a preprocessor limit report within hidden tags.
     * 
     * @see http://en.wikipedia.org/wiki/Wikipedia:Template_limits#How_can_you_find_out.3F
     * @param string $text
     * @return string
     */
    static public function removeNewPPLimitReports($html)
    {
        // The "s" modifier means the "." meta-character will include newlines. 
        // The "?" means the "+" quantifier is not greedy, thus will not remove 
        // text between pages when importing document transcriptions.
        $html = preg_replace("/<!-- \nNewPP limit report.+?-->/s", '', $html);
        return $html;
    }
}
