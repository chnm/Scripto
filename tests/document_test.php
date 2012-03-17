<?php
require_once 'config.php';

/**
 * Test the base Scripto_Document class.
 */
class TestDocument extends UnitTestCase
{
    private $_testDocumentId;
    private $_testDocument;
    
    /**
     * Use __construct() instead of setUp() because it's unnecessary to set up 
     * the test case before every test method.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_testDocumentId = TEST_DOCUMENT_ID;
        
        require_once TEST_ADAPTER_FILENAME;
        require_once 'Scripto/Service/MediaWiki.php';
        require_once 'Scripto/Document.php';
        
        // Instantiate the Scripto_Document object and set it.
        $testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        $this->_testDocument = new Scripto_Document(
            $this->_testDocumentId, 
            new $testAdapterClassName, 
            new Scripto_Service_MediaWiki(TEST_MEDIAWIKI_API_URL, false) 
        );
    }
    
    public function testGetId()
    {
        $this->assertEqual($this->_testDocumentId, $this->_testDocument->getId());
    }
    
    public function testGetTitle()
    {
        $this->assertIsA($this->_testDocument->getTitle(), 'string');
    }
    
    /**
     * Set the page for subsequent tests.
     */
    public function testPageIsValid()
    {
        // Assert a page has not been set yet.
        $this->assertNull($this->_testDocument->getPageId(), 'The document page ID was prematurely set');
        
        // Assert a page can be set (in this case, the first page).
        $this->_testDocument->setPage(null);
        $this->assertNotNull($this->_testDocument->getPageId(), 'The document page ID was not set');
        
        // Assert the decoding the base title works.
        $baseTitle = Scripto_Document::encodeBaseTitle($this->_testDocument->getId(), $this->_testDocument->getPageId());
        $decodedBaseTitle = Scripto_Document::decodeBaseTitle($baseTitle);
        
        $this->assertEqual($decodedBaseTitle[0], $this->_testDocumentId, 'Something went wrong during base title encoding/decoding. Document ID does not match');
        $this->assertEqual($decodedBaseTitle[1], $this->_testDocument->getPageId(), 'Something went wrong during base title encoding/decoding. Page ID does not match');
    }
    
    public function testGetPageName()
    {
        $this->assertIsA($this->_testDocument->getPageName(), 'string');
    }
    
    public function testGetBaseTitle()
    {
        $this->assertIsA($this->_testDocument->getBaseTitle(), 'string');
    }
    
    public function testGetPages()
    {
        $this->assertIsA($this->_testDocument->getPages(), 'array');
    }
    
    public function testGetFirstPageId()
    {
        $firstPageId = $this->_testDocument->getFirstPageId();
        $this->assertTrue((is_int($firstPageId) || is_string($firstPageId)));
    }
    
    public function testGetPageFileUrl()
    {
        $this->assertIsA($this->_testDocument->getPageFileUrl(), 'string');
    }
    
    public function testGetTranscriptionPageMediawikiUrl()
    {
        $this->assertIsA($this->_testDocument->getTranscriptionPageMediawikiUrl(), 'string');
    }
    
    public function testGetTalkPageMediawikiUrl()
    {
        $this->assertIsA($this->_testDocument->getTalkPageMediawikiUrl(), 'string');
    }
}
