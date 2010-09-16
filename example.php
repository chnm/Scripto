<?php

// Path to directory containing Zend Framework.
define('ZEND_PATH', '');

// Path to directory containing the Scripto library.
define('SCRIPTO_PATH', '');

// URL to the MediaWiki installation API.
define('MEDIAWIKI_API_URL', '');

// Set the include path to Zend and Scripto libraries.
set_include_path(get_include_path() 
                 . PATH_SEPARATOR . ZEND_PATH 
                 . PATH_SEPARATOR . SCRIPTO_PATH);

// Must set the Content-Type header to correctly display UTF-8.
header('Content-Type: text/html; charset=utf-8');

// Get the document ID and page ID.
$documentId = isset($_GET['documentId']) ? $_GET['documentId'] : null;
$pageId = isset($_GET['pageId']) ? $_GET['pageId'] : null;

// Set the Adapter object.
require_once 'Scripto/Adapter/Example.php';
$adapter = new Scripto_Adapter_Example;

// Set the Document object.
require_once 'Scripto/Document.php';
$doc = new Scripto_Document($documentId, MEDIAWIKI_API_URL, $adapter);

// Must set the current page first.
$doc->setPage($pageId);

if (isset($_POST['submit_login'])) {
    $doc->login($_POST['username'], $_POST['password']);
}

if (isset($_POST['submit_logout'])) {
    $doc->logout();
}

// Display the edit and logout forms and/or process the edit form if the current 
// user has permission to edit.
if ($doc->canEdit()) {
    if (isset($_POST['submit_transcription'])) {
        $doc->editTranscriptionPage($_POST['transcription']);
    }
?>
<script type="text/javascript">
    
    // From: /mediawiki/skins/common/wikibits.js
    var mwEditButtons = [];
    var mwCustomEditButtons = [];
    
    function hookEvent(hookName, hookFunct) {
        addHandler(window, hookName, hookFunct);
    }
    
    function addHandler( element, attach, handler ) {
        if( window.addEventListener ) {
            element.addEventListener( attach, handler, false );
        } else if( window.attachEvent ) {
            element.attachEvent( 'on' + attach, handler );
        }
    }
    
    // See: http://en.wikipedia.org/wiki/MediaWiki:Common.js/edit.js
    // See: getEditToolbar() in /mediawiki/includes/EditPage.php      
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_bold.png",
        "speedTip": "Bold text",
        "tagOpen": "'''",
        "tagClose": "'''",
        "sampleText": "Bold text"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_italic.png",
        "speedTip": "Italic text",
        "tagOpen": "''",
        "tagClose": "''",
        "sampleText": "Italic text"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_link.png",
        "speedTip": "Internal link",
        "tagOpen": "[[",
        "tagClose": "]]",
        "sampleText": "Link title"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_extlink.png",
        "speedTip": "External link (remember http:// prefix)",
        "tagOpen": "[",
        "tagClose": "]",
        "sampleText": "http://www.example.com link title"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_headline.png",
        "speedTip": "Level 2 headline",
        "tagOpen": "\n== ",
        "tagClose": " ==\n",
        "sampleText": "Headline text"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_image.png",
        "speedTip": "Embedded file",
        "tagOpen": "[[File:",
        "tagClose": "]]",
        "sampleText": "Example.jpg"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_media.png",
        "speedTip": "File link",
        "tagOpen": "[[Media:",
        "tagClose": "]]",
        "sampleText": "Example.ogg"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_math.png",
        "speedTip": "Mathematical formula (LaTeX)",
        "tagOpen": "<math>",
        "tagClose": "</math>",
        "sampleText": "Insert formula here"
    });
     mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_nowiki.png",
        "speedTip": "Ignore wiki formatting",
        "tagOpen": "<nowiki>",
        "tagClose": "</nowiki>",
        "sampleText": "Insert non-formatted text here"
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_sig.png",
        "speedTip": "Your signature with timestamp",
        "tagOpen": "--~~~~",
        "tagClose": "",
        "sampleText": ""
    });
    mwEditButtons.push({
        "imageFile": "/mediawiki/skins/common/images/button_hr.png",
        "speedTip": "Horizontal line (use sparingly)",
        "tagOpen": "\n----\n",
        "tagClose": "",
        "sampleText": ""
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/c/c8/Button_redirect.png",
        "speedTip": "Redirect",
        "tagOpen": "#REDIRECT [[",
        "tagClose": "]]",
        "sampleText": "Target page name"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/c/c9/Button_strike.png",
        "speedTip": "Strike",
        "tagOpen": "<s>",
        "tagClose": "</s>",
        "sampleText": "Strike-through text"
    });
    mwCustomEditButtons.push({
         "imageFile": "http://upload.wikimedia.org/wikipedia/en/1/13/Button_enter.png",
        "speedTip": "Line break",
        "tagOpen": "<br />",
        "tagClose": "",
        "sampleText": ""
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/8/80/Button_upper_letter.png",
        "speedTip": "Superscript",
        "tagOpen": "<sup>",
        "tagClose": "</sup>",
        "sampleText": "Superscript text"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/7/70/Button_lower_letter.png",
        "speedTip": "Subscript",
        "tagOpen": "<sub>",
        "tagClose": "</sub>",
        "sampleText": "Subscript text"
    });
 
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/5/58/Button_small.png",
        "speedTip": "Small",
        "tagOpen": "<small>",
        "tagClose": "</small>",
        "sampleText": "Small Text"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/3/34/Button_hide_comment.png",
        "speedTip": "Insert hidden Comment",
        "tagOpen": "<!-- ",
        "tagClose": " -->",
        "sampleText": "Comment"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/1/12/Button_gallery.png",
        "speedTip": "Insert a picture gallery",
        "tagOpen": "\n<gallery>\n",
        "tagClose": "\n</gallery>",
        "sampleText": "Image:Example.jpg|Caption1\nImage:Example.jpg|Caption2"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/f/fd/Button_blockquote.png",
        "speedTip": "Insert block of quoted text",
        "tagOpen": "<blockquote>\n",
        "tagClose": "\n</blockquote>",
        "sampleText": "Block quote"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/en/6/60/Button_insert_table.png",
        "speedTip": "Insert a table",
        "tagOpen": '{| class="wikitable"\n|',
        "tagClose": "\n|}",
        "sampleText": "-\n! header 1\n! header 2\n! header 3\n|-\n| row 1, cell 1\n| row 1, cell 2\n| row 1, cell 3\n|-\n| row 2, cell 1\n| row 2, cell 2\n| row 2, cell 3"
    });
    mwCustomEditButtons.push({
        "imageFile": "http://upload.wikimedia.org/wikipedia/commons/7/79/Button_reflink.png",
        "speedTip": "Insert a reference",
        "tagOpen": "<ref>",
        "tagClose": "</ref>",
        "sampleText": "Insert footnote text here"
    });
</script>
<script src="/mediawiki/skins/common/edit.js" type="text/javascript"><!-- Core MediaWiki edit toolbar functions --></script>
<div id="toolbar"><!-- MediaWiki edit toolbar --></div>

<form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
    <textarea name="transcription" id="wpTextbox1" rows="24" cols="80"><?php echo $doc->getTranscriptionPageWikitext(); ?></textarea><br />
    <input type="submit" name="submit_transcription" value="Submit" />
</form>

<form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
    <input type="submit" name="submit_logout" value="Logout" />
</form>
<?php

// Display the login form if the current user does not have permission to edit.
} else {
?>
<form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
    <p>Username: <input type="input" name="username" /></p>
    <p>Password: <input type="password" name="password" /></p>
    <p><input type="submit" name="submit_login" value="Login" /></p>
</form>
<?php
}

foreach ($doc->getPages() as $pageId => $pageName) {
    echo '<a href="?documentId=' . urlencode($doc->getId()) . '&pageId=' . urlencode($pageId) . '">' . $pageName . '</a><br />';
}

echo $doc->getTranscriptionPageHtml();