<?php
/**
 * @package Scripto
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @see Scripto
 */
require_once 'Scripto.php';

/**
 * @see Scripto_Exception.
 */
require_once 'Scripto/Exception.php';

/**
 * Represents a Scripto document. Serves as a connector object between the 
 * external system API and MediaWiki API.
 * 
 * @package Scripto
 */
class Scripto_Document
{
    /**
     * The prefix used in the base title to keep MediaWiki from capitalizing the 
     * first character.
     */
    const BASE_TITLE_PREFIX = '.';
    
    /**
     * The delimiter used to separate the document and page IDs in the base 
     * title.
     */
    const BASE_TITLE_DELIMITER = '.';
    
    /**
     * The maximum bytes MediaWiki allows for a page title.
     */
    const TITLE_BYTE_LIMIT = 256;
    
    /**
     * @var string The document ID provided by the external system.
     */
    protected $_id;
    
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
     * @var string The document title provided by the external system.
     */
    protected $_title;
    
    /**
     * @var string The document page name provided by the external system.
     */
    protected $_pageName;
    
    /**
     * @var string The document page ID provided by the external system.
     */
    protected $_pageId;
    
    /**
     * @var string The base title (i.e. without a namespace) of the 
     * corresponding MediaWiki page.
     */
    protected $_baseTitle;
    
    /**
     * @var array Information about the current transcription page.
     */
    protected $_transcriptionPageInfo;
    
    /**
     * @var array Information about the current talk page.
     */
    protected $_talkPageInfo;
    
    /**
     * Construct the Scripto document object.
     * 
     * @param string|int $id The unique document identifier.
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param array|Scripto_Service_MediaWiki $mediawiki {@link Scripto::mediawikiFactory()}
     */
    public function __construct($id, 
                                Scripto_Adapter_Interface $adapter, 
                                Scripto_Service_MediaWiki $mediawiki)
    {
        // Document IDs must not be empty strings, null, or false.
        if (!strlen($id) || is_null($id) || false === $id) {
            throw new Scripto_Exception('The document ID is invalid.');
        }
        
        // Check if the document exists.
        if (!$adapter->documentExists($id)) {
            throw new Scripto_Exception("The specified document does not exist: {$this->_id}");
        }
        
        $this->_id = $id;
        $this->_adapter = $adapter;
        $this->_mediawiki = $mediawiki;
        $this->_title = $this->_adapter->getDocumentTitle($id);
    }
    
    /**
     * Set the current document page.
     * 
     * Sets the current page ID, the base title used by MediaWiki, and 
     * information about the MediaWiki transcription and talk pages.
     * 
     * @param string|null $pageId The unique page identifier.
     */
    public function setPage($pageId)
    {
        // Set to the first page if the provided page is NULL or FALSE.
        if (null === $pageId || false === $pageId) {
            $pageId = $this->getFirstPageId();
        }
        
        // Check if the page exists.
        if (!$this->_adapter->documentPageExists($this->_id, $pageId)) {
            throw new Scripto_Exception("The specified page does not exist: $pageId");
        }
        
        // Mint the page title used by MediaWiki.
        $baseTitle = self::encodeBaseTitle($this->_id, $pageId);
        
        // Check if the base title is under the maximum character length.
        if (self::TITLE_BYTE_LIMIT < strlen($this->_baseTitle)) {
            throw new Scripto_Exception('The document ID and/or page ID are too long to set the provided page.');
        }
        
        // Set information about the transcription and talk pages.
        $this->_transcriptionPageInfo = $this->_getPageInfo($baseTitle);
        $this->_talkPageInfo = $this->_getPageInfo('Talk:' . $baseTitle);
        
        $this->_pageId = $pageId;
        $this->_pageName = $this->_adapter->getDocumentPageName($this->_id, $pageId);
        $this->_baseTitle = $baseTitle;
    }
    
    /**
     * Get this document's ID.
     * 
     * @return string|int
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * Get this document's title.
     */
    public function getTitle()
    {
        return $this->_title;
    }
    
    /**
     * Get this document page's name.
     */
    public function getPageName()
    {
        return $this->_pageName;
    }
    
    /**
     * Get this document's current page ID.
     * 
     * @return string|int
     */
    public function getPageId()
    {
        return $this->_pageId;
    }
    
    /**
     * Get this document's current base title.
     * 
     * @return string
     */
    public function getBaseTitle()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the base title.');
        }
        return $this->_baseTitle;
    }
    
    /**
     * Get information about the current MediaWiki transcription page.
     * 
     * @return array
     */
    public function getTranscriptionPageInfo()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting information about the transcription page.');
        }
        return $this->_transcriptionPageInfo;
    }
    
    /**
     * Get information about the current MediaWiki talk page.
     * 
     * @return array
     */
    public function getTalkPageInfo()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting information about the talk page.');
        }
        return $this->_talkPageInfo;
    }
    
    /**
     * Get all of this document's pages from the adapter.
     * 
     * @uses Scripto_Adapter_Interface::getDocumentPages()
     * @return array
     */
    public function getPages()
    {
        return (array) $this->_adapter->getDocumentPages($this->_id);
    }
    
    /**
     * Get this document's first page ID from the adapter.
     * 
     * @uses Scripto_Adapter_Interface::getDocumentFirstPageId()
     * @return array
     */
    public function getFirstPageId()
    {
        return $this->_adapter->getDocumentFirstPageId($this->_id);
    }
    
    /**
     * Get this document's current page file URL from the adapter.
     * 
     * @uses Scripto_Adapter_Interface::getDocumentPageFileUrl()
     * @return string
     */
    public function getPageFileUrl()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the page file URL.');
        }
        return $this->_adapter->getDocumentPageFileUrl($this->_id, $this->_pageId);
    }
    
    /**
     * Get the MediaWiki URL for the current transcription page.
     * 
     * @return string
     */
    public function getTranscriptionPageMediawikiUrl()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page MediaWiki URL.');
        }
        return $this->_getPageMediawikiUrl($this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki URL for the current talk page.
     * 
     * @return string
     */
    public function getTalkPageMediawikiUrl()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page MediaWiki URL.');
        }
        return $this->_getPageMediawikiUrl('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki transcription page wikitext for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionWikitext()
     * @return string The transcription wikitext.
     */
    public function getTranscriptionPageWikitext()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page wikitext.');
        }
        return $this->_mediawiki->getLatestRevisionWikitext($this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki talk page wikitext for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionWikitext()
     * @return string The talk wikitext.
     */
    public function getTalkPageWikitext()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page wikitext.');
        }
        return $this->_mediawiki->getLatestRevisionWikitext('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki transcription page HTML for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionHtml()
     * @return string The transcription HTML.
     */
    public function getTranscriptionPageHtml()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page HTML.');
        }
        return $this->_mediawiki->getLatestRevisionHtml($this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki talk page HTML for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionHtml()
     * @return string The talk HTML.
     */
    public function getTalkPageHtml()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page HTML.');
        }
        return $this->_mediawiki->getLatestRevisionHtml('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki transcription page plain text for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionHtml()
     * @return string The transcription page plain text.
     */
    public function getTranscriptionPagePlainText()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page plain text.');
        }
        return html_entity_decode(strip_tags($this->_mediawiki->getLatestRevisionHtml($this->_baseTitle)));
    }
    
    /**
     * Get the MediaWiki talk plain text for the current page.
     * 
     * @uses Scripto_Service_MediaWiki::getLatestRevisionHtml()
     * @return string The talk plain text.
     */
    public function getTalkPagePlainText()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page plain text.');
        }
        return html_entity_decode(strip_tags($this->_mediawiki->getLatestRevisionHtml('Talk:' . $this->_baseTitle)));
    }
    
    /**
     * Get the MediaWiki transcription page revision history for the current page.
     * 
     * @param int $limit The number of revisions to return.
     * @param int $startRevisionId The revision ID from which to start.
     * @return array
     */
    public function getTranscriptionPageHistory($limit = 10, $startRevisionId = null)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page history.');
        }
        return $this->_getPageHistory($this->_baseTitle, $limit, $startRevisionId);
    }
    
    /**
     * Get the MediaWiki talk page revision history for the current page.
     * 
     * @param int $limit The number of revisions to return.
     * @param int $startRevisionId The revision ID from which to start.
     * @return array
     */
    public function getTalkPageHistory($limit = 10, $startRevisionId = null)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page history.');
        }
        return $this->_getPageHistory('Talk:' . $this->_baseTitle, $limit, $startRevisionId);
    }
    
    /**
     * Determine if the current user can edit the MediaWiki transcription page.
     * 
     * @return bool
     */
    public function canEditTranscriptionPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the user can edit the transcription page.');
        }
        return $this->_canEdit($this->_transcriptionPageInfo['protections']);
    }
    
    /**
     * Determine if the current user can edit the MediaWiki talk page.
     * 
     * @return bool
     */
    public function canEditTalkPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the user can edit the talk page.');
        }
        return $this->_canEdit($this->_talkPageInfo['protections']);
    }
    
    /**
     * Edit the MediaWiki transcription page for the current document.
     * 
     * @uses Scripto_Service_MediaWiki::edit()
     * @param string $text The wikitext of the transcription.
     */
    public function editTranscriptionPage($text)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before editing the transcription page.');
        }
        $this->_mediawiki->edit($this->_baseTitle, 
                                $text, 
                                $this->_transcriptionPageInfo['edit_token']);
    }
    
    /**
     * Edit the MediaWiki talk page for the current document.
     * 
     * @uses Scripto_Service_MediaWiki::edit()
     * @param string $text The wikitext of the transcription.
     */
    public function editTalkPage($text)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before editing the talk page.');
        }
        $this->_mediawiki->edit('Talk:' . $this->_baseTitle, 
                                $text, 
                                $this->_talkPageInfo['edit_token']);
    }
    
    /**
     * Protect the current transcription page.
     */
    public function protectTranscriptionPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before protecting the transcription page.');
        }
        $this->_protectPage($this->_baseTitle, $this->_transcriptionPageInfo['protect_token']);
        
        // Update information about this page.
        $this->_transcriptionPageInfo = $this->_getPageInfo($this->_baseTitle);
    }
    
    /**
     * Protect the current talk page.
     */
    public function protectTalkPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before protecting the talk page.');
        }
        $this->_protectPage('Talk:' . $this->_baseTitle, $this->_talkPageInfo['protect_token']);
        
        // Update information about this page.
        $this->_talkPageInfo = $this->_getPageInfo('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Unprotect the current transcription page.
     */
    public function unprotectTranscriptionPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before unprotecting the transcription page.');
        }
        $this->_unprotectPage($this->_baseTitle, $this->_transcriptionPageInfo['protect_token']);
        
        // Update information about this page.
        $this->_transcriptionPageInfo = $this->_getPageInfo($this->_baseTitle);
    }
    
    /**
     * Unprotect the current talk page.
     */
    public function unprotectTalkPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before unprotecting the talk page.');
        }
        $this->_unprotectPage('Talk:' . $this->_baseTitle, $this->_talkPageInfo['protect_token']);
        
        // Update information about this page.
        $this->_talkPageInfo = $this->_getPageInfo('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Watch the current page.
     * 
     * Watching a transcription page implies watching its talk page.
     * 
     * @uses Scripto_Service_MediaWiki::watch()
     */
    public function watchPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before watching the page.');
        }
        $this->_mediawiki->watch($this->_baseTitle);
    }
    
    /**
     * Unwatch the current page.
     * 
     * Unwatching a transcription page implies unwatching its talk page.
     * 
     * @uses Scripto_Service_MediaWiki::watch()
     */
    public function unwatchPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before unwatching the page.');
        }
        $this->_mediawiki->watch($this->_baseTitle, null, array('unwatch' => true));
    }
    
    /**
     * Determine whether the current transcription page is edit protected.
     * 
     * @return bool
     */
    public function isProtectedTranscriptionPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the transcription page is protected.');
        }
        return $this->_isProtectedPage($this->_transcriptionPageInfo['protections']);
    }
    
    /**
     * Determine whether the current talk page is edit protected.
     * 
     * @return bool
     */
    public function isProtectedTalkPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the talk page is protected.');
        }
        return $this->_isProtectedPage($this->_talkPageInfo['protections']);
    }
    
     /**
     * Determine whether the current user is watching the current page.
     * 
     * @return bool
     */
    public function isWatchedPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the current user is watching the page.');
        }
        return $this->_transcriptionPageInfo['watched'];
    }
    
    /**
     * Determine whether all of this document's transcription pages were already 
     * exported to the external system.
     * 
     * @uses Scripto_Adapter_Interface::documentTranscriptionIsImported()
     * @return bool
     */
    public function isExported()
    {
        return $this->_adapter->documentTranscriptionIsImported($this->_id);
    }
    
    /**
     * Determine whether the current transcription page was already exported to 
     * the external system.
     * 
     * @uses Scripto_Adapter_Interface::documentPageTranscriptionIsImported()
     * @return bool
     */
    public function isExportedPage()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether it is imported.');
        }
        return $this->_adapter->documentPageTranscriptionIsImported($this->_id, $this->_pageId);
    }
    
    /**
     * Export the document page transcription to the external system by calling 
     * the adapter.
     * 
     * @uses Scripto_Adapter_Interface::importDocumentPageTranscription()
     * @param string $type The type of text to set, valid options are 
     * plain_text, html, and wikitext.
     */
    public function exportPage($type = 'plain_text')
    {
        switch ($type) {
            case 'plain_text':
                $text = $this->getTranscriptionPagePlainText();
                break;
            case 'html':
                $text = $this->getTranscriptionPageHtml();
                break;
            case 'wikitext':
                $text = $this->getTranscriptionPageWikitext();
                break;
            default:
                throw new Scripto_Exception('The provided import type is invalid.');
        }
        $this->_adapter->importDocumentPageTranscription($this->_id, 
                                                         $this->_pageId, 
                                                         trim($text));
    }
    
    /**
     * Export the entire document transcription to the external system by 
     * calling the adapter.
     * 
     * @uses Scripto_Adapter_Interface::importDocumentTranscription()
     * @param string $type The type of text to set, valid options are 
     * plain_text, html, and wikitext.
     * @param string $pageDelimiter The delimiter used to stitch pages together.
     */
    public function export($type = 'plain_text', $pageDelimiter = "\n")
    {
        $text = array();
        foreach ($this->getPages() as $pageId => $pageName) {
            $baseTitle = self::encodeBaseTitle($this->_id, $pageId);
            switch ($type) {
                case 'plain_text':
                    $text[] = html_entity_decode(strip_tags($this->_mediawiki->getLatestRevisionHtml($baseTitle)));
                    break;
                case 'html':
                    $text[] = $this->_mediawiki->getLatestRevisionHtml($baseTitle);
                    break;
                case 'wikitext':
                    $text[] = $this->_mediawiki->getLatestRevisionWikitext($baseTitle);
                    break;
                default:
                    throw new Scripto_Exception('The provided import type is invalid.');
            }
        }
        $text = implode($pageDelimiter, array_map('trim', $text));
        $this->_adapter->importDocumentTranscription($this->_id, trim($text));
    }
    
    /**
     * Determine if the current user can edit the specified MediaWiki page.
     * 
     * @uses Scripto_Service_MediaWiki::getUserInfo()
     * @param array $pageProtections
     * @return bool
     */
    protected function _canEdit(array $pageProtections)
    {
        $userInfo = $this->_mediawiki->getUserInfo('rights');
        
        // Users without edit rights cannot edit pages.
        if (!in_array('edit', $userInfo['query']['userinfo']['rights'])) {
            return false;
        }
        
        // Users with edit rights can edit unprotected pages.
        if (empty($pageProtections)) {
            return true;
        }
        
        // Iterate the page protections.
        foreach ($pageProtections as $pageProtection) {
            
            // The page is edit-protected.
            if ('edit' == $pageProtection['type']) {
                
                // Users with edit and protect rights can edit protected pages.
                if (in_array('protect', $userInfo['query']['userinfo']['rights'])) {
                    return true;
                
                // Users with edit but without protect rights cannot edit 
                // protected pages.
                } else {
                    return false;
                }
            }
        }
        
        // Users with edit rights can edit pages that are not edit-protected.
        return true;
    }
    
    /**
     * Determine whether the provided protections contain an edit protection.
     * 
     * @param array $pageProtections The page protections from the page info:
     * {@link Scripto_Document::$_transcriptionPageInfo} or 
     * {@link Scripto_Document::$_talkPageInfo}.
     * @return bool
     */
    protected function _isProtectedPage(array $pageProtections)
    {
        // There are no protections.
        if (empty($pageProtections)) {
            return false;
        }
        
        // Iterate the page protections.
        foreach ($pageProtections as $pageProtection) {
            // The page is edit protected.
            if ('edit' == $pageProtection['type'] || 'create' == $pageProtection['type']) {
                return true;
            }
        }
        
        // There are no edit protections.
        return false;
    }
    
    /**
     * Protect the specified page.
     * 
     * @uses Scripto_Service_MediaWiki::protect()
     * @param string $title
     * @param string $protectToken
     */
    protected function _protectPage($title, $protectToken)
    {
        if ($this->_mediawiki->pageCreated($title)) {
            $protections = 'edit=sysop';
        } else {
            $protections = 'create=sysop';
        }
        $this->_mediawiki->protect($title, $protections, $protectToken);
    }
    
    /**
     * Unprotect the specified page.
     * 
     * @uses Scripto_Service_MediaWiki::protect()
     * @param string $title
     * @param string $protectToken
     */
    protected function _unprotectPage($title, $protectToken)
    {
        if ($this->_mediawiki->pageCreated($title)) {
            $protections = 'edit=all';
        } else {
            $protections = 'create=all';
        }
        $this->_mediawiki->protect($title, $protections, $protectToken);
    }
    
    /**
     * Get the MediaWiki URL for the specified page.
     * 
     * @uses Scripto_Service_MediaWiki::getSiteInfo()
     * @param string $title
     * @return string
     */
    protected function _getPageMediawikiUrl($title)
    {
        $siteInfo = $this->_mediawiki->getSiteInfo();
        return $siteInfo['query']['general']['server'] 
             . str_replace('$1', $title, $siteInfo['query']['general']['articlepath']);
    }
    
    /**
     * Get information for the specified page.
     * 
     * @uses Scripto_Service_MediaWiki::getInfo()
     * @param string $title
     * @return array
     */
    protected function _getPageInfo($title)
    {
        $params = array('inprop' => 'protection|talkid|subjectid|url|watched', 
                        'intoken' => 'edit|move|delete|protect');
        $response = $this->_mediawiki->getInfo($title, $params);
        $page = current($response['query']['pages']);
        $pageInfo = array('page_id'            => isset($page['pageid']) ? $page['pageid'] : null, 
                          'namespace_index'    => isset($page['ns']) ? $page['ns'] : null, 
                          'mediawiki_title'    => isset($page['title']) ? $page['title'] : null, 
                          'last_revision_id'   => isset($page['lastrevid']) ? $page['lastrevid'] : null, 
                          'counter'            => isset($page['counter']) ? $page['counter'] : null, 
                          'length'             => isset($page['length']) ? $page['length'] : null, 
                          'start_timestamp'    => isset($page['starttimestamp']) ? $page['starttimestamp'] : null, 
                          'edit_token'         => isset($page['edittoken']) ? $page['edittoken'] : null, 
                          'move_token'         => isset($page['movetoken']) ? $page['movetoken'] : null, 
                          'delete_token'       => isset($page['deletetoken']) ? $page['deletetoken'] : null, 
                          'protect_token'      => isset($page['protecttoken']) ? $page['protecttoken'] : null, 
                          'protections'        => isset($page['protection']) ? $page['protection'] : null, 
                          'talk_id'            => isset($page['talkid']) ? $page['talkid'] : null, 
                          'mediawiki_full_url' => isset($page['fullurl']) ? $page['fullurl'] : null, 
                          'mediawiki_edit_url' => isset($page['editurl']) ? $page['editurl'] : null, 
                          'watched'            => isset($page['watched']) ? true: false, 
                          'redirect'           => isset($page['redirect']) ? true: false, 
                          'new'                => isset($page['new']) ? true: false);
        return $pageInfo;
    }
    
    /**
     * Get the revisions for the specified page.
     * 
     * @uses Scripto_Service_MediaWiki::getRevisions()
     * @param string $title
     * @param int $limit
     * @param int $startRevisionId
     * @return array
     */
    protected function _getPageHistory($title, $limit = 10, $startRevisionId = null)
    {
        $revisions = array();
        do {
            $response = $this->_mediawiki->getRevisions(
                $title, 
                array('rvstartid' => $startRevisionId, 
                      'rvlimit'   => 100, 
                      'rvprop'    => 'ids|flags|timestamp|user|comment|size')
            );
            $page = current($response['query']['pages']);
            
            // Return if the page has not been created.
            if (array_key_exists('missing', $page)) {
                return $revisions;
            }
            
            foreach ($page['revisions'] as $revision) {
                
                $action = Scripto::getChangeAction(array('comment' => $revision['comment']));
                
                // Build the revisions.
                $revisions[] = array(
                    'revision_id' => $revision['revid'], 
                    'parent_id'   => $revision['parentid'], 
                    'user'        => $revision['user'], 
                    'timestamp'   => $revision['timestamp'], 
                    'comment'     => $revision['comment'], 
                    'size'        => $revision['size'], 
                    'action'      => $action, 
                );
                
                // Break out of the loops if limit has been reached.
                if ($limit == count($revisions)) {
                    break 2;
                }
            }
            
            // Set the query continue, if any.
            if (isset($response['query-continue'])) {
                $startRevisionId = $response['query-continue']['revisions']['rvstartid'];
            } else {
                $startRevisionId = null;
            }
            
        } while ($startRevisionId);
        
        return $revisions;
    }
    
    /**
     * Encode a base title that enables fail-safe document page transport 
     * between the external system, Scripto, and MediaWiki.
     * 
     * The base title is the base MediaWiki page title that corresponds to the 
     * document page. Encoding is necessary to allow all Unicode characters in 
     * document and page IDs, even those not allowed in URL syntax and MediaWiki 
     * naming conventions. Encoding in Base64 allows the title to be decoded.
     * 
     * The base title has four parts:
     * <ol>
     *     <li>A title prefix to keep MediaWiki from capitalizing the first 
     *     character</li>
     *     <li>A URL-safe Base64 encoded document ID</li>
     *     <li>A delimiter between the encoded document ID and page ID</li>
     *     <li>A URL-safe Base64 encoded page ID</li>
     * </ol>
     * 
     * @link http://en.wikipedia.org/wiki/Base64#URL_applications
     * @link http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_%28technical_restrictions%29
     * @param string|int $documentId The document ID
     * @param string|int $pageId The page ID
     * @return string The encoded base title
     */
    static public function encodeBaseTitle($documentId, $pageId)
    {
        return self::BASE_TITLE_PREFIX
             . Scripto_Document::base64UrlEncode($documentId)
             . self::BASE_TITLE_DELIMITER
             . Scripto_Document::base64UrlEncode($pageId);
    }
    
    /**
     * Decode the base title.
     * 
     * @param string|int $baseTitle
     * @return array An array containing the document ID and page ID
     */
    static public function decodeBaseTitle($baseTitle)
    {
        // First remove the title prefix.
        $baseTitle = ltrim($baseTitle, self::BASE_TITLE_PREFIX);
        // Create an array containing the document ID and page ID.
        $baseTitle = explode(self::BASE_TITLE_DELIMITER, $baseTitle);
        // URL-safe Base64 decode the array and return it.
        return array_map('Scripto_Document::base64UrlDecode', $baseTitle);
    }
    
    /**
     * Encode a string to URL-safe Base64.
     * 
     * @link http://en.wikipedia.org/wiki/Base64#URL_applications
     * @param string $str
     * @return string
     */
    static public function base64UrlEncode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }
    
    /**
     * Decode a string from a URL-safe Base64.
     * 
     * @param string $str
     * @return string
     */
    static public function base64UrlDecode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }
}
