<?php
/**
 * @copyright © 2010, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Scripto.php';
require_once 'Scripto/Exception.php';

/**
 * Represents a Scripto document and its pages.
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
     * @var string This document's title.
     */
    protected $_title;
    
    /**
     * @var string The document page ID provided by the external system.
     */
    protected $_pageId;
    
    /**
     * @var string The base title of the corresponding MediaWiki page.
     */
    protected $_baseTitle;
    
    /**
     * Construct the Scripto document object.
     * 
     * @param string|int $id The unique document identifier.
     * @param Scripto_Adapter_Interface $adapter The adapter object.
     * @param array|Scripto_Service_MediaWiki $mediawiki {@link Scripto::mediawikiFactory()}
     */
    public function __construct($id, 
                                Scripto_Adapter_Interface $adapter, 
                                $mediawiki)
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
        $this->_mediawiki = Scripto::mediawikiFactory($mediawiki);
        $this->_title = $this->_adapter->getDocumentTitle($id);
    }
    
    /**
     * Set the current page ID and the base title used by MediaWiki.
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
        
        $this->_pageId = $pageId;
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
        return $this->_baseTitle;
    }
    
    /**
     * Get the structured pages from the adapter.
     * 
     * @return array
     */
    public function getPages()
    {
        // Get the structured pages from the adapter.
        return (array) $this->_adapter->getDocumentPages($this->_id);
    }
    
    /**
     * Get the page image URL.
     * 
     * @return string
     */
    public function getPageImageUrl()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the page image URL.');
        }
        return $this->_adapter->getDocumentPageImageUrl($this->_id, $this->_pageId);
    }
    
    /**
     * Get the first page ID of the document.
     * 
     * @return array
     */
    public function getFirstPageId()
    {
        return $this->_adapter->getDocumentFirstPageId($this->_id);
    }
    
    /**
     * Get the MediaWiki transcription page wikitext for the current page.
     * 
     * @return string The transcription wikitext.
     */
    public function getTranscriptionPageWikitext()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page wikitext.');
        }
        return $this->_mediawiki->getPageWikitext($this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki talk page wikitext for the current page.
     * 
     * @return string The talk wikitext.
     */
    public function getTalkPageWikitext()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page wikitext.');
        }
        return $this->_mediawiki->getPageWikitext('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki transcription page HTML for the current page.
     * 
     * @return string The transcription HTML.
     */
    public function getTranscriptionPageHtml()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page HTML.');
        }
        return $this->_mediawiki->getPageHtml($this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki talk page HTML for the current page.
     * 
     * @return string The talk HTML.
     */
    public function getTalkPageHtml()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page HTML.');
        }
        return $this->_mediawiki->getPageHtml('Talk:' . $this->_baseTitle);
    }
    
    /**
     * Get the MediaWiki transcription page plain text for the current page.
     * 
     * @return string The transcription page plain text.
     */
    public function getTranscriptionPagePlainText()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the transcription page plain text.');
        }
        return strip_tags($this->_mediawiki->getPageHtml($this->_baseTitle));
    }
    
    /**
     * Get the MediaWiki talk plain text for the current page.
     * 
     * @return string The talk plain text.
     */
    public function getTalkPagePlainText()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before getting the talk page plain text.');
        }
        return strip_tags($this->_mediawiki->getPageHtml('Talk:' . $this->_baseTitle));
    }
    
    /**
     * Get an HTML preview of the provided wikitext.
     * 
     * @param string $wikitext The wikitext.
     * @return string The wikitext parsed as HTML.
     */
    public function getPreview($wikitext)
    {
        return $this->_mediawiki->getPreview($wikitext);
    }
    
    /**
    * Determine if the current user can edit the MediaWiki page.
    * 
    * It is possible to restrict anonymous editing in MediaWiki.
    * @link http://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_editing_of_all_pages
    * 
    * It is possible to restrict account creation in MediaWiki.
    * @link http://www.mediawiki.org/wiki/Manual:Preventing_access#Restrict_account_creation
    * 
    * @return bool
    */
    public function canEdit()
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before determining whether the user can edit it.');
        }
        
        $userInfo = $this->_mediawiki->getUserInfo();
        
        // Users without edit rights cannot edit pages.
        if (!in_array('edit', $userInfo['query']['userinfo']['rights'])) {
            return false;
        }
        
        $pageProtections = $this->_mediawiki->getPageProtections($this->_baseTitle);
        
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
     * Edit the MediaWiki transcription page for the current document.
     * 
     * @param string $text The wikitext of the transcription
     */
    public function editTranscriptionPage($text)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before editing the transcription page.');
        }
        $this->_mediawiki->editPage($this->_baseTitle, $text);
    }
    
    /**
     * Edit the MediaWiki talk page for the current document.
     * 
     * @param string $text The wikitext of the transcription
     */
    public function editTalkPage($text)
    {
        if (is_null($this->_pageId)) {
            throw new Scripto_Exception('The document page must be set before editing the talk page.');
        }
        $this->_mediawiki->editPage('Talk:' . $this->_baseTitle, $text);
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
     *   1) A title prefix to keep MediaWiki from capitalizing the first character
     *   2) A URL-safe Base64 encoded document ID
     *   3) A delimiter between the encoded document ID and page ID
     *   4) A URL-safe Base64 encoded page ID
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
    
    /**
     * Import the document page transcription to the external system by calling 
     * the adapter.
     * 
     * @param string $type The type of text to set, valid options are 
     *                     plain_text, html, and wikitext.
     */
    public function importDocumentPageTranscription($type = 'plain_text')
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
                break;
        }
        $this->_adapter->importDocumentPageTranscription($this->_id, 
                                                         $this->_pageId, 
                                                         $text);
    }
    
    /**
     * Import the entire document transcription to the external system by 
     * calling the adapter.
     * 
     * @param string $type The type of text to set, valid options are 
     *                     plain_text, html, and wikitext.
     * @param string $pageDelimiter The delimiter used to stitch pages together.
     */
    public function importDocumentTranscription($type = 'plain_text', 
                                                $pageDelimiter = "\n")
    {
        $text = array();
        foreach ($this->getPages() as $pageId => $pageName) {
            $baseTitle = self::encodeBaseTitle($this->_id, $pageId);
            switch ($type) {
                case 'plain_text':
                    $text[] = strip_tags($this->_mediawiki->getPageHtml($baseTitle));
                    break;
                case 'html':
                    $text[] = $this->_mediawiki->getPageHtml($baseTitle);
                    break;
                case 'wikitext':
                    $text[] = $this->_mediawiki->getPageWikitext($baseTitle);
                    break;
                default:
                    throw new Scripto_Exception('The provided import type is invalid.');
                    break;
            }
        }
        $this->_adapter->importDocumentTranscription($this->_id, 
                                                     implode($pageDelimiter, $text));
    }
}