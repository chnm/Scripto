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
|}";
    
    /**
     * When getting a preview and page HTML, MediaWiki returns an HTML comment 
     * containing a dynamic "NewPP limit report." Here, this is removed prior to 
     * asserting valid get responses.
     */
    const TEST_EXPECTED_HTML = '<p><b>Bold text</b>
<i>Italic text</i>
<a href="http://www.example.com" class="external text" rel="nofollow">link title</a>
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
<table class="gallery" cellspacing="0" cellpadding="0">
	<tr>
		<td><div class="gallerybox" style="width: 155px;">
			<div style="height: 152px;">Example.jpg</div>
			<div class="gallerytext">
<p>Caption1
</p>
			</div>
		</div></td>
		<td><div class="gallerybox" style="width: 155px;">
			<div style="height: 152px;">Example.jpg</div>
			<div class="gallerytext">
<p>Caption2
</p>
			</div>
		</div></td>
	</tr>
</table>
<blockquote>
Block quote
</blockquote>
<table class="wikitable">

<tr>
<th> header 1
</th><th> header 2
</th><th> header 3
</th></tr>
<tr>
<td> row 1, cell 1
</td><td> row 1, cell 2
</td><td> row 1, cell 3
</td></tr>
<tr>
<td> row 2, cell 1
</td><td> row 2, cell 2
</td><td> row 2, cell 3
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
            $this->assertTrue(is_array($editCredentials), 'Login did not work');
            
            // Assert logout works.
            $this->_testMediawiki->logout();
            $editCredentials = $this->_testMediawiki->getEditCredentials(self::TEST_TITLE);
            $this->assertTrue(is_null($editCredentials), 'Logout did not work');
            
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
        // Assert the test page's preview is valid. Remove dynamic HTML 
        // comments.
        $testPagePreview = $this->_testMediawiki->getPreview(self::TEST_WIKITEXT);
        $this->assertTrue(self::TEST_EXPECTED_HTML == $this->_removeHtmlComments($testPagePreview), 'The test page preview HTML is invalid');
        
        // Clear the page before testing edit page. Resetting the database or 
        // deleting the page is preferable, but resetting is too involved and 
        // Scripto_Service_MediaWiki does not implement a delete page feature 
        // because deleting requires special (sysops) permissions.
        $this->_testMediawiki->editPage(self::TEST_TITLE, '');
        $text = $this->_testMediawiki->getPageWikitext(self::TEST_TITLE);
        $this->assertTrue('' == $text, 'Clearing the test page did not work');
        
        // Edit the page with test text.
        $this->_testMediawiki->editPage(self::TEST_TITLE, self::TEST_WIKITEXT);
        
        // Assert the test page's Wikitext is valid.
        $textPageWikitext = $this->_testMediawiki->getPageWikitext(self::TEST_TITLE);
        $this->assertTrue(self::TEST_WIKITEXT == $textPageWikitext, 'Editing the test page with test wikitext did not work ');
        
        // Assert the test page's HTML is valid. Remove dynamic HTML comments.
        $testPageHtml = $this->_testMediawiki->getPageHtml(self::TEST_TITLE);
        $this->assertTrue(self::TEST_EXPECTED_HTML == $this->_removeHtmlComments($testPageHtml), 'The test page HTML is invalid');
        
    }
    
    private function _removeHtmlComments($text)
    {
        // Must include "s" modifier so "." matches new lines.
        return preg_replace('/<!--.*-->/s', '', $text);
    }
}