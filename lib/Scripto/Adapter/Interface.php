<?php
/**
 * @package Scripto
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Interface for Scripto adapter classes.
 * 
 * @package Scripto
 */
interface Scripto_Adapter_Interface
{
    /**
     * Indicate whether the document exists in the external system.
     * 
     * Implementers must provide a unique identifier for every document. We 
     * highly recommend using unique keys from the external database whenever 
     * possible (e.g. the document ID).
     * 
     * @param int|string $documentId The unique document ID
     * @return bool True: it exists; false: it does not exist
     */
    public function documentExists($documentId);
    
    /**
     * Indicate whether the document page exists in the external system.
     * 
     * Implementers must provide a unique identifier for every document page per 
     * document. We highly recommend using unique keys from the external 
     * database whenever possible (e.g. the page ID).
     * 
     * @param int|string $documentId The unique document ID
     * @param int|string $pageId The unique page ID
     * @return bool True: it exists; false: it does not exist
     */
    public function documentPageExists($documentId, $pageId);
    
    /**
     * Get all the pages belonging to the document.
     * 
     * Implementers must provide a unique identifier for every page per 
     * document. These IDs must have corresponding page names, and must be in 
     * sequential page order. Page IDs must be unique but do not have to be in 
     * natural order. Page names do not have to be unique.
     * 
     * For the page IDs we highly recommend using unique keys from the external 
     * database whenever possible (e.g. the file ID). This page ID will be used 
     * to query the adapter for page file URLs, so they must be no ambiguity.
     * 
     * The return value must follow this format:
     * array([pageId] => [pageName], [...])
     * 
     * Example return values:
     * array(2011 => 'Title Page', 
     *       1999 => 'Page 1', 
     *       4345 => 'Page 2')
     * 
     * array('page_1' => 1, 
     *       'page_2' => 2, 
     *       'page_3' => 3)
     * 
     * @param int|string $documentId The unique document ID
     * @return array An array containing page identifiers as keys and page names 
     * as values, in sequential page order.
     */
    public function getDocumentPages($documentId);
    
    /**
     * Get the URL of the specified document page file.
     * 
     * @param int|string $documentId The unique document ID
     * @param int|string $pageId The unique page ID
     * @return string The page file URL
     */
    public function getDocumentPageFileUrl($documentId, $pageId);
    
    /**
     * Get the first page of the document.
     * 
     * @param int|string $documentId The document ID
     * @return int|string
     */
    public function getDocumentFirstPageId($documentId);
    
    /**
     * Get the title of the document.
     * 
     * @param int|string $documentId The document ID
     * @return string
     */
    public function getDocumentTitle($documentId);
    
    /**
     * Get the name of the document page.
     * 
     * @param int|string $documentId The document ID
     * @param int|string $pageId The unique page ID
     * @return string
     */
    public function getDocumentPageName($documentId, $pageId);
    
    /**
     * Indicate whether the document transcription has been imported.
     * 
     * @param int|string $documentId The document ID
     * @return bool True: has been imported; false: has not been imported
     */
    public function documentTranscriptionIsImported($documentId);
    
    /**
     * Indicate whether the document page transcription has been imported.
     * 
     * @param int|string $documentId The document ID
     * @param int|string $pageId The page ID
     */
    public function documentPageTranscriptionIsImported($documentId, $pageId);
    
    /**
     * Import a document page's transcription into the external system.
     * 
     * @param int|string $documentId The document ID
     * @param int|string $pageId The page ID
     * @param string $text The text to import
     * @return bool True: success; false: fail
     */
    public function importDocumentPageTranscription($documentId, $pageId, $text);
    
    /**
     * Import an entire document's transcription into the external system.
     * 
     * @param int|string The document ID
     * @param string The text to import
     * @return bool True: success; false: fail
     */
    public function importDocumentTranscription($documentId, $text);
}
