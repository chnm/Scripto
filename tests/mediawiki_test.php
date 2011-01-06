<?php
require_once 'config.php';

/**
 * Test the MediaWiki API client.
 * 
 * It appears that SimpleTest does not allow cookies to be set from an outside 
 * method, so full coverage is impossible when edit credentials are required. 
 * Until a solution is found, this test case will fail if the test MediaWiki 
 * requires credentials.
 */
class TestMediawiki extends UnitTestCase
{
    const TEST_TITLE = 'SGVsbG8gV29ybGQ=';
    const TEST_TEXT = "'''Bold text'''
''Italic text''
[[Link title]]
[http://www.example.com link title]

== Headline text ==
[[File:Example.jpg]]
[[Media:Example.ogg]]
<math>Insert formula here</math>
<nowiki>Insert non-formatted text here</nowiki>
--~~~~

----

<s>Strike-through text</s>
<br />
<sup>Superscript text</sup>
<sub>Subscript text</sub>
<small>Small Text</small>
<!-- Comment -->

<gallery>
Image:Example.jpg|Caption1
Image:Example.jpg|Caption2
</gallery>
<blockquote>
Block quote
</blockquote>
{| class=\"wikitable\"
|-
! header 1
! header 2
! header 3
|-
| row 1, cell 1
| row 1, cell 2
| row 1, cell 3
|-
| row 2, cell 1
| row 2, cell 2
| row 2, cell 3
|}
<ref>Insert footnote text here</ref>";
    
    private $_testMediawiki;
    private $_testEditCredentials;
    
    /**
     * Use __construct() instead of setUp() because it's unnecessary to set up 
     * the test case before every test method.
     */
    public function __construct()
    {
        parent::__construct();
        
        // Do not pass cookies to a browser when testing.
        require_once 'Scripto/Service/MediaWiki.php';
        $this->_testMediawiki = new Scripto_Service_MediaWiki(TEST_MEDIAWIKI_API_URL, 
                                                              TEST_MEDIAWIKI_DB_NAME, 
                                                              false);
    }
    
    public function testCredentials()
    {
        // Assert credentials are valid.
        $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
        $this->assertTrue((is_array($editCredentials) || is_null($editCredentials)), 'Edit credentials must be NULL or an array. ' . gettype($editCredentials) . ' given');
        
        // Test login and logout if credentials are required.
        if (is_null($editCredentials)) {
            
            // Assert login works.
            $this->_testMediawiki->login(TEST_MEDIAWIKI_USERNAME, TEST_MEDIAWIKI_PASSWORD);
            $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
            $this->assertTrue(is_array($editCredentials), 'Login did not work.');
            
            // Assert logout works.
            $this->_testMediawiki->logout();
            $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
            $this->assertTrue(is_null($editCredentials), 'Logout did not work.');
            
            // Login and get credentials again to continue testing.
            $this->_testMediawiki->login(TEST_MEDIAWIKI_USERNAME, TEST_MEDIAWIKI_PASSWORD);
            $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
        }
        
        // Assert credential keys are valid.
        $this->assertTrue(array_key_exists('edittoken', $editCredentials), 'Edit credentials array must contain a "edittoken" key');
        $this->assertTrue(array_key_exists('basetimestamp', $editCredentials), 'Edit credentials array must contain a "basetimestamp" key');
    }
    
    public function testEditPage()
    {
        // Clear the page before testing edit page. Resetting the database or 
        // deleting the page is preferable, but resetting is too involved and 
        // Scripto_Service_MediaWiki does not implement a delete page feature 
        // because deleting requires special (sysops) permissions.
        $this->_testMediawiki->editPage(self::TEST_TITLE, '');
        
        // Edit the page.
        $this->_testMediawiki->editPage(self::TEST_TITLE, self::TEST_TEXT);
        
        // Assert get pages successful.
    }
}