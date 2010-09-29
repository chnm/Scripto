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
    "imageFile": "images/button_bold.png",
    "speedTip": "Bold text",
    "tagOpen": "'''",
    "tagClose": "'''",
    "sampleText": "Bold text"
});
mwEditButtons.push({
    "imageFile": "images/button_italic.png",
    "speedTip": "Italic text",
    "tagOpen": "''",
    "tagClose": "''",
    "sampleText": "Italic text"
});
mwEditButtons.push({
    "imageFile": "images/button_link.png",
    "speedTip": "Internal link",
    "tagOpen": "[[",
    "tagClose": "]]",
    "sampleText": "Link title"
});
mwEditButtons.push({
    "imageFile": "images/button_extlink.png",
    "speedTip": "External link (remember http:// prefix)",
    "tagOpen": "[",
    "tagClose": "]",
    "sampleText": "http://www.example.com link title"
});
mwEditButtons.push({
    "imageFile": "images/button_headline.png",
    "speedTip": "Level 2 headline",
    "tagOpen": "\n== ",
    "tagClose": " ==\n",
    "sampleText": "Headline text"
});
mwEditButtons.push({
    "imageFile": "images/button_image.png",
    "speedTip": "Embedded file",
    "tagOpen": "[[File:",
    "tagClose": "]]",
    "sampleText": "Example.jpg"
});
mwEditButtons.push({
    "imageFile": "images/button_media.png",
    "speedTip": "File link",
    "tagOpen": "[[Media:",
    "tagClose": "]]",
    "sampleText": "Example.ogg"
});
mwEditButtons.push({
    "imageFile": "images/button_math.png",
    "speedTip": "Mathematical formula (LaTeX)",
    "tagOpen": "<math>",
    "tagClose": "</math>",
    "sampleText": "Insert formula here"
});
 mwEditButtons.push({
    "imageFile": "images/button_nowiki.png",
    "speedTip": "Ignore wiki formatting",
    "tagOpen": "<nowiki>",
    "tagClose": "</nowiki>",
    "sampleText": "Insert non-formatted text here"
});
mwEditButtons.push({
    "imageFile": "images/button_sig.png",
    "speedTip": "Your signature with timestamp",
    "tagOpen": "--~~~~",
    "tagClose": "",
    "sampleText": ""
});
mwEditButtons.push({
    "imageFile": "images/button_hr.png",
    "speedTip": "Horizontal line (use sparingly)",
    "tagOpen": "\n----\n",
    "tagClose": "",
    "sampleText": ""
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_redirect.png",
    "speedTip": "Redirect",
    "tagOpen": "#REDIRECT [[",
    "tagClose": "]]",
    "sampleText": "Target page name"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_strike.png",
    "speedTip": "Strike",
    "tagOpen": "<s>",
    "tagClose": "</s>",
    "sampleText": "Strike-through text"
});
mwCustomEditButtons.push({
     "imageFile": "images/Button_enter.png",
    "speedTip": "Line break",
    "tagOpen": "<br />",
    "tagClose": "",
    "sampleText": ""
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_upper_letter.png",
    "speedTip": "Superscript",
    "tagOpen": "<sup>",
    "tagClose": "</sup>",
    "sampleText": "Superscript text"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_lower_letter.png",
    "speedTip": "Subscript",
    "tagOpen": "<sub>",
    "tagClose": "</sub>",
    "sampleText": "Subscript text"
});

mwCustomEditButtons.push({
    "imageFile": "images/Button_small.png",
    "speedTip": "Small",
    "tagOpen": "<small>",
    "tagClose": "</small>",
    "sampleText": "Small Text"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_hide_comment.png",
    "speedTip": "Insert hidden Comment",
    "tagOpen": "<!-- ",
    "tagClose": " -->",
    "sampleText": "Comment"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_gallery.png",
    "speedTip": "Insert a picture gallery",
    "tagOpen": "\n<gallery>\n",
    "tagClose": "\n</gallery>",
    "sampleText": "Image:Example.jpg|Caption1\nImage:Example.jpg|Caption2"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_blockquote.png",
    "speedTip": "Insert block of quoted text",
    "tagOpen": "<blockquote>\n",
    "tagClose": "\n</blockquote>",
    "sampleText": "Block quote"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_insert_table.png",
    "speedTip": "Insert a table",
    "tagOpen": '{| class="wikitable"\n|',
    "tagClose": "\n|}",
    "sampleText": "-\n! header 1\n! header 2\n! header 3\n|-\n| row 1, cell 1\n| row 1, cell 2\n| row 1, cell 3\n|-\n| row 2, cell 1\n| row 2, cell 2\n| row 2, cell 3"
});
mwCustomEditButtons.push({
    "imageFile": "images/Button_reflink.png",
    "speedTip": "Insert a reference",
    "tagOpen": "<ref>",
    "tagClose": "</ref>",
    "sampleText": "Insert footnote text here"
});