<?php
require_once 'config.php';

class TestAdapter extends UnitTestCase {
    
    private $_testAdapterClassName;
    private $_testDocumentId;
    private $_testAdapter;
    private $_testDocumentPages;
    
    public function setUp()
    {
        $this->_testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        $this->_testDocumentId = TEST_DOCUMENT_ID;
    }
    
    public function testAdapterIsValid()
    {
        // Assert adapter file exists.
        $filename = TEST_ADAPTER_FILENAME;
        $this->assertTrue(file_exists($filename), 'Example adapter file does not exist');
        
        // Assert adapter file is instance of Scripto_Adapter_Interface.
        require_once $filename;
        $adapter = new $this->_testAdapterClassName;
        $this->assertIsA($adapter, 'Scripto_Adapter_Interface', 'Example adapter is not an instance of Scripto_Adapter_Interface');
        
        $this->_testAdapter = $adapter;
    }
    
    public function testDocumentIsValid()
    {
        // Assert document ID exists.
        $this->assertTrue($this->_testAdapter->documentExists($this->_testDocumentId), 'Document ID does not exist');
        
        // Assert valid document pages format.
        $documentPages = $this->_testAdapter->getDocumentPages($this->_testDocumentId);
        $this->assertIsA($documentPages, 'array', 'Document pages are an invalid format');
        $this->assertTrue(count($documentPages), 'Document pages should not be empty');
        
        $this->_testDocumentPages = $documentPages;
    }
    
    public function testDocumentPagesAreValid()
    {
        // Assert document first page is valid and exists.
        $documentFirstPageId = $this->_testAdapter->getDocumentFirstPageId($this->_testDocumentId);
        $this->assertTrue((is_int($documentFirstPageId) || is_string($documentFirstPageId)), 'Document first page ID is invalid type');
        $this->assertTrue(array_key_exists($documentFirstPageId, $this->_testDocumentPages), 'Document first page ID does not exist');
        
        // Iterate all document pages.
        foreach ($this->_testDocumentPages as $pageId => $pageName) {
            // Assert document pages exist.
            $documentPageExists = $this->_testAdapter->documentPageExists($this->_testDocumentId, $pageId);
            $this->assertIdentical($documentPageExists, true, "Document page ID \"$pageId\" does not exist");
            
            // Assert document page URLs are valid. There's no consistant, 
            // reliable, and lightweight way to validate a URL, even with 
            // regular expressions, so just check to see if it returns a string.
            $documentPageImageUrl = $this->_testAdapter->getDocumentPageImageUrl($this->_testDocumentId, $pageId);
            $this->assertIsA($documentPageImageUrl, 'string', "Document page ID \"$pageId\" has an invalid image URL");
        }
    }
    
    public function testImportTranscriptions()
    {
        // Must install a parallel external system to test imports. This may be 
        // to involved to be feasible for most people.
    }
}
