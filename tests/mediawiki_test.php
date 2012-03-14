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
    
    /**
     * Contains no dynamic or custom Wikitext (e.g. signatures, media file 
     * links, references.).
     */
    const TEST_WIKITEXT = "'''Bold text'''
''Italic text''
[http://www.example.com link title]
== Headline text ==
<nowiki>Insert non-formatted text here</nowiki>
----
<s>Strike-through text</s>
<br />
<sup>Superscript text</sup>
<sub>Subscript text</sub>
<small>Small Text</small>
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
|}";
    
    /**
     * When getting a preview and page HTML, MediaWiki returns an HTML comment 
     * containing a dynamic "NewPP limit report." Here, this is removed prior to 
     * asserting valid get responses.
     */
    const TEST_EXPECTED_HTML = '<p><b>Bold text</b>
<i>Italic text</i>
<a rel="nofollow" class="external text" href="http://www.example.com">link title</a>
</p>
<h2> <span class="mw-headline" id="Headline_text"> Headline text </span></h2>
<p>Insert non-formatted text here
</p>
<hr />
<p><s>Strike-through text</s>
<br />
<sup>Superscript text</sup>
<sub>Subscript text</sub>
<small>Small Text</small>
</p>
<blockquote>
Block quote
</blockquote>
<table class="wikitable">

<tr>
<th> header 1
</th>
<th> header 2
</th>
<th> header 3
</th></tr>
<tr>
<td> row 1, cell 1
</td>
<td> row 1, cell 2
</td>
<td> row 1, cell 3
</td></tr>
<tr>
<td> row 2, cell 1
</td>
<td> row 2, cell 2
</td>
<td> row 2, cell 3
</td></tr></table>


';
    
    const TEST_EXPECTED_PREVIEW = '';
    
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
        $this->_testMediawiki = new Scripto_Service_MediaWiki(TEST_MEDIAWIKI_API_URL, false);
    }
    
    public function testCredentials()
    {
        // Test login and logout if username and password is provided.
        if (TEST_MEDIAWIKI_USERNAME && TEST_MEDIAWIKI_PASSWORD) {
            
            // Assert login works. Throws an error if login is unsuccessful.
            $this->_testMediawiki->login(TEST_MEDIAWIKI_USERNAME, TEST_MEDIAWIKI_PASSWORD);
            
            // Assert logout works.
            $this->_testMediawiki->logout();
            $userInfo = $this->_testMediawiki->getUserInfo();
            $this->assertTrue(isset($userInfo['query']['userinfo']['anon']), 'Logout unsuccessful');
        }
    }
    
    public function testEditPage()
    {
        // Assert the test page's preview is valid. Remove dynamic HTML comments.
        $testPagePreview = $this->_testMediawiki->getPreview(self::TEST_WIKITEXT);
        $this->assertEqual(self::TEST_EXPECTED_HTML, $this->_removeHtmlComments($testPagePreview), 'The test page preview HTML is invalid');
        
        // Clear the page before testing edit page. Resetting the database or 
        // deleting the page is preferable, but resetting is too involved and 
        // Scripto_Service_MediaWiki does not implement a delete page feature 
        // because deleting requires special (sysops) permissions.
        $this->_testMediawiki->edit(self::TEST_TITLE, '.');
        $text = $this->_testMediawiki->getLatestRevisionWikitext(self::TEST_TITLE);
        $this->assertEqual('.', $text, 'Clearing the test page did not work');
        
        // Edit the page with test text.
        $this->_testMediawiki->edit(self::TEST_TITLE, self::TEST_WIKITEXT);
        
        // Assert the test page's Wikitext is valid.
        $textPageWikitext = $this->_testMediawiki->getLatestRevisionWikitext(self::TEST_TITLE);
        $this->assertEqual(self::TEST_WIKITEXT, $textPageWikitext, 'Editing the test page with test wikitext did not work ');
        
        // Assert the test page's HTML is valid. Remove dynamic HTML comments.
        $testPageHtml = $this->_testMediawiki->getLatestRevisionHtml(self::TEST_TITLE);
        $this->assertEqual(self::TEST_EXPECTED_HTML, $this->_removeHtmlComments($testPageHtml), 'The test page HTML is invalid');
        
    }
    
    private function _removeHtmlComments($text)
    {
        // Must include "s" modifier so "." matches new lines.
        return preg_replace('/<!--.*-->/s', '', $text);
    }
}