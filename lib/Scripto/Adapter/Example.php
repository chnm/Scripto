<?php
/**
 * @copyright © 2010-2011, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Scripto/Adapter/Interface.php';

/**
 * An example adapter for a hypothetical CMS.
 * 
 * @see Scripto_Adapter_Interface
 */
class Scripto_Adapter_Example implements Scripto_Adapter_Interface
{
    /**
     * Example document data.
     * 
     * For example purposes the document data are stored following this format:
     * 
     * {documentId} => array(
     *     'document_title' => {documentTitle}, 
     *     'document_pages' => array(
     *         {pageId} => array(
     *             'page_name' => {pageName}, 
     *             'page_image_url' => {pageImageUrl}
     *         )
     *     )
     * ) 
     * 
     * Other adapters will likely get relevant data using the CMS API, and not 
     * hardcode them like this example. Be sure to URL encode the document and 
     * page IDs when transporting over HTTP. For example:
     * 
     * documentId: Request for Purchase of Liver Oil & Drum Heads
     * pageId: xbe/XBE02001.jpg
     * ?documentId=Request+for+Purchase+of+Liver+Oil+%26+Drum+Heads&pageId=xbe%2FXBE02001.jpg
     * 
     * These example documents are from Center for History and New Media Papers 
     * of the War Department and Library of Congress American Memory.
     * 
     * @var array
     */
    private $_documents = array(
        // Example of the preferred way to set the document and page IDs using 
        // unique keys. See: http://wardepartmentpapers.org/document.php?id=16344
        16344 => array(
            'document_title' => 'Return of articles received and expended; work done at Springfield Massachusetts armory', 
            'document_pages' => array(
                67799 => array(
                    'page_name' => 'Letter Outside', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07001.jpg'
                ), 
                67800 => array(
                    'page_name' => 'Letter Body', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07002.jpg'
                ), 
                67801 => array(
                    'page_name' => 'Worksheet 1, Outside', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07003.jpg'
                ), 
                67802 => array(
                    'page_name' => 'Worksheet 1, Page 1', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07004.jpg'
                ), 
                67803 => array(
                    'page_name' => 'Worksheet 1, Page 2', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07005.jpg'
                ), 
                67804 => array(
                    'page_name' => 'Worksheet 2, Outside', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07006.jpg'
                ), 
                67805 => array(
                    'page_name' => 'Worksheet 2, Page 1', 
                    'page_image_url' => 'http://wardepartmentpapers.org/images/medium/zto/ZTO07007.jpg'
                )
            )
        ), 
        // An alternate way to set the document using a document title as the 
        // document ID and the image file path as the page ID. See: http://books.google.com/books?id=eAuOQMmGEYIC&lpg=PA515&ots=PtWRBKDZbf&pg=PA515
        // %5BFacsimile%20of%5D%20letter%20to%20Messrs.%20O.%20P.%20Hall%20et%20al%20from%20Lincoln.
        '[Facsimile of] letter to Messrs. O. P. Hall et al from Lincoln.' => array(
            'document_title' => '[Facsimile of] letter to Messrs. O. P. Hall et al from Lincoln.', 
            'document_pages' => array(
                // rbc%2Flprbscsm%2Fscsm0455%2F001r.jpg
                'rbc/lprbscsm/scsm0455/001r.jpg' => array(
                    'page_name' => '001r', 
                    'page_image_url' => 'http://memory.loc.gov/service/rbc/lprbscsm/scsm0455/001r.jpg'
                ), 
                'rbc/lprbscsm/scsm0455/002r.jpg' => array(
                    'page_name' => '002r', 
                    'page_image_url' => 'http://memory.loc.gov/service/rbc/lprbscsm/scsm0455/002r.jpg'
                ), 
                'rbc/lprbscsm/scsm0455/003r.jpg' => array(
                    'page_name' => '003r', 
                    'page_image_url' => 'http://memory.loc.gov/service/rbc/lprbscsm/scsm0455/003r.jpg'
                ), 
                'rbc/lprbscsm/scsm0455/004r.jpg' => array(
                    'page_name' => '004r', 
                    'page_image_url' => 'http://memory.loc.gov/service/rbc/lprbscsm/scsm0455/004r.jpg'
                )
            )
        )
    );
    
    public function documentExists($documentId)
    {
        return array_key_exists($documentId, $this->_documents);
    }
    
    public function documentPageExists($documentId, $pageId)
    {
        return array_key_exists($pageId, $this->_documents[$documentId]['document_pages']);
    }
    
    public function getDocumentPages($documentId)
    {
        $pages = array();
        foreach ($this->_documents[$documentId]['document_pages'] as $pageId => $page) {
            $pages[$pageId] = $page['page_name'];
        }
        return $pages;
    }
    
    public function getDocumentPageImageUrl($documentId, $pageId)
    {
        return $this->_documents[$documentId]['document_pages'][$pageId]['page_image_url'];
    }
    
    public function getDocumentFirstPageId($documentId)
    {
        return key($this->_documents[$documentId]['document_pages']);
    }
    
    public function getDocumentTitle($documentId)
    {
        return $this->_documents[$documentId]['document_title'];
    }
    
    public function importDocumentPageTranscription($documentId, $pageId, $text)
    {
        return false;
    }
    
    public function importDocumentTranscription($documentId, $text)
    {
        return false;
    }
}
