<?php
require_once 'config.php';

/**
 * Test the base Scripto class.
 */
class TestScripto extends UnitTestCase
{
    private $_testMediawikiUsername;
    private $_testMediaWikiPassword;
    private $_testDocumentId;
    private $_testScripto;
    
    /**
     * Use __construct() instead of setUp() because it's unnecessary to set up 
     * the test case before every test method.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->_testMediawikiUsername = TEST_MEDIAWIKI_USERNAME;
        $this->_testMediawikiPassword = TEST_MEDIAWIKI_PASSWORD;
        $this->_testDocumentId = TEST_DOCUMENT_ID;
        
        require_once TEST_ADAPTER_FILENAME;
        require_once 'Scripto/Service/MediaWiki.php';
        require_once 'Scripto.php';
        
        // Instantiate the Scripto object and set it.
        $testAdapterClassName = TEST_ADAPTER_CLASS_NAME;
        $this->_testScripto = new Scripto(
            new $testAdapterClassName, 
            new Scripto_Service_MediaWiki(TEST_MEDIAWIKI_API_URL, false) 
        );
    }
    
    public function testDocumentExists()
    {
        $this->assertIsA($this->_testScripto->documentExists($this->_testDocumentId), 'bool');
    }
    
    public function testGetDocument()
    {
        $this->assertIsA($this->_testScripto->getDocument($this->_testDocumentId), 'Scripto_Document');
    }
    
    public function testLogin()
    {
        if ($this->_testMediawikiUsername && $this->_testMediawikiPassword) {
            $this->_testScripto->login($this->_testMediawikiUsername, $this->_testMediawikiPassword);
            $this->assertTrue($this->_testScripto->isLoggedIn());
        }
    }
    
    public function testCanExport()
    {
        $this->assertIsA($this->_testScripto->canExport(), 'bool');
    }
    
    public function testCanProtect()
    {
        $this->assertIsA($this->_testScripto->canProtect(), 'bool');
    }
    
    public function testGetUserName()
    {
        $this->assertIsA($this->_testScripto->getUserName(), 'string');
    }
    
    public function testGetUserDocumentPages()
    {
        $this->assertIsA($this->_testScripto->getUserDocumentPages(), 'array');
    }
    
    public function testGetRecentChanges()
    {
        $this->assertIsA($this->_testScripto->getRecentChanges(), 'array');
    }
    
    public function testGetWatchlist()
    {
        if ($this->_testScripto->isLoggedIn()) {
            $this->assertIsA($this->_testScripto->getWatchlist(), 'array');
        }
    }
    
    public function testGetAllDocuments()
    {
        $this->assertIsA($this->_testScripto->getAllDocuments(), 'array');
    }
    
    public function testLogout()
    {
        if ($this->_testScripto->isLoggedIn()) {
            $this->_testScripto->logout();
            $this->assertFalse($this->_testScripto->isLoggedIn());
        }
    }
}
