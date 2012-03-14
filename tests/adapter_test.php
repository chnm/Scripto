<?php
require_once 'config.php';

/**
 * Test the selected adapter.
 * 
 * This tests the external system API by calling its adapter directly and 
 * testing for expected return values. It tests one document thoroughly as an 
 * indication that all others are valid.
 */
class TestAdapter extends UnitTestCase
{
    
    private $_testAdapterFilename;
    private $_testAdapterClassName;
    private $_testDocumentId;
    private $_testAdapter;
    private $_testDocumentPages;
    
    /**
     * Use __construct() instead of setUp() because it's unnecessary to set up 
     * the test case before every test method.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_testAdapterFilename = TEST_ADAPTER_FILENAME;
        $this->_testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        $this->_testDocumentId = TEST_DOCUMENT_ID;
    }
    
    public function testAdapterIsValid()
    {
        // Assert adapter file exists.
        $this->assertTrue(file_exists($this->_testAdapterFilename), 'Example adapter file does not exist');
        
        // Assert adapter file is instance of Scripto_Adapter_Interface.
        require_once $this->_testAdapterFilename;
        $adapter = new $this->_testAdapterClassName;
        $this->assertIsA($adapter, 'Scripto_Adapter_Interface', 'Example adapter is not an instance of Scripto_Adapter_Interface');
        
        $this->_testAdapter = $adapter;
    }
    
    public function testDocumentIsValid()
    {
        // Assert document ID is valid and exists.
        $this->assertTrue((is_int($this->_testDocumentId) || is_string($this->_testDocumentId)), 'Document ID must be int or string. ' . gettype($this->_testDocumentId) . ' given');
        $this->assertTrue($this->_testAdapter->documentExists($this->_testDocumentId), "Document ID \"{$this->_testDocumentId}\" does not exist");
        
        // Assert valid document pages format.
        $documentPages = $this->_testAdapter->getDocumentPages($this->_testDocumentId);
        $this->assertIsA($documentPages, 'array', 'Document pages must be an array. ' . gettype($documentPages) . ' given');
        $this->assertTrue(count($documentPages), 'Document pages must not be empty');
        
        $this->_testDocumentPages = $documentPages;
    }
    
    public function testDocumentPagesAreValid()
    {
        // Assert document first page is valid and exists.
        $documentFirstPageId = $this->_testAdapter->getDocumentFirstPageId($this->_testDocumentId);
        $this->assertTrue((is_int($documentFirstPageId) || is_string($documentFirstPageId)), 'Document first page ID must be int or string. ' . gettype($documentFirstPageId) . ' given');
        $this->assertTrue(array_key_exists($documentFirstPageId, $this->_testDocumentPages), "Document first page ID \"$documentFirstPageId\" does not exist");
        
        // Iterate all document pages.
        foreach ($this->_testDocumentPages as $pageId => $pageName) {
            // Assert document pages exist.
            $documentPageExists = $this->_testAdapter->documentPageExists($this->_testDocumentId, $pageId);
            $this->assertIdentical($documentPageExists, true, "Document page ID \"$pageId\" does not exist");
            
            // Assert document page URLs are valid. There's no consistant, 
            // reliable, and lightweight way to validate a URL, even with 
            // regular expressions, so just check to see if it returns a string.
            $documentPageImageUrl = $this->_testAdapter->getDocumentPageFileUrl($this->_testDocumentId, $pageId);
            $this->assertIsA($documentPageImageUrl, 'string', "Document page image URL for page ID \"$pageId\" must be a string. " . gettype($documentPageImageUrl) . " given");
        }
    }
    
    public function testImportTranscriptions()
    {
        // Must install a parallel external system to test imports. This may be 
        // too involved to be feasible for most people.
    }
}
