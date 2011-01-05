<?php
require_once 'config.php';

class TestDocument extends UnitTestCase
{
    
    private $_testDocument;
    
    /**
     * Use __construct() instead of setUp() because it's unnecessary to set up 
     * the test case before every test method.
     */
    public function __construct()
    {
        parent::__construct();
        
        // Assume TestAdapter has already verified the test adapter and test 
        // document.
        $testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        require_once TEST_ADAPTER_FILENAME;
        
        // Instantiate the Scripto_Document object and set it.
        require 'Scripto/Document.php';
        $this->_testDocument = new Scripto_Document(TEST_DOCUMENT_ID, 
                                                    TEST_MEDIAWIKI_API_URL, 
                                                    TEST_MEDIAWIKI_DB_NAME, 
                                                    new $testAdapterClassName);
    }
    
    public function testDocumentIsValid()
    {
        // Assert a page has not been set yet.
        $this->assertNull($this->_testDocument->getPageId(), 'The document page ID was prematurely set');
        
        // Assert a page can be set (in this case, the first page).
        $this->_testDocument->setPage(null);
        $this->assertNotNull($this->_testDocument->getPageId(), 'The document page ID was not set');
        
        // Assert accessor methods return expected values.
        $this->assertIdentical(TEST_DOCUMENT_ID, $this->_testDocument->getId());
        
    }
}
