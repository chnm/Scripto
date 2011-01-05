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
        
        require_once 'Scripto/Service/MediaWiki.php';
        $this->_testMediawiki = new Scripto_Service_MediaWiki(TEST_MEDIAWIKI_API_URL, 
                                                              TEST_MEDIAWIKI_DB_NAME);
    }
    
    public function testCredentials()
    {
        // Assert edit credentials are valid.
        $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
        $this->assertIsA($editCredentials, 'array', 'Edit credentials must be an array. ' . gettype($editCredentials) . ' given');
        if (is_array($editCredentials)) {
            $this->assertTrue(array_key_exists('edittoken', $editCredentials), 'Edit credentials array must contain a "edittoken" key');
            $this->assertTrue(array_key_exists('basetimestamp', $editCredentials), 'Edit credentials array must contain a "basetimestamp" key');
        }
    }
    
    public function testPages()
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