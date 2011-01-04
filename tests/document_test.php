<?php
require_once 'config.php';

class TestDocument extends UnitTestCase {
    
    private $_testAdapterClassName;
    private $_testDocumentId;
    
    public function setUp()
    {
        $this->_testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        $this->_testDocumentId = TEST_DOCUMENT_ID;
    }
    
    public function testDocumentIsValid()
    {
        $this->assertTrue(true);
    }
}
