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

function setupToolbar(toolbar) {
	// Don't generate buttons for browsers which don't fully
	// support it.
	// but don't assume wpTextbox1 is always here
	var textboxes = document.getElementsByTagName( 'textarea' );
	if ( !textboxes.length ) {
		// No toolbar if we can't find any textarea
		return false;
	}

	// Only check for selection capability if the textarea is visible - errors will occur otherwise - just because
	// the textarea is not visible, doesn't mean we shouldn't build out the toolbar though - it might have been replaced
	// with some other kind of control
	for ( var i = 0; i < mwEditButtons.length; i++ ) {
		mwInsertEditButton( toolbar, mwEditButtons[i] );
	}
	for ( var i = 0; i < mwCustomEditButtons.length; i++ ) {
		mwInsertEditButton( toolbar, mwCustomEditButtons[i] );
	}
	return true;
}

function mwInsertEditButton( parent, item ) {
	var image = document.createElement( 'img' );
	image.width = 23;
	image.height = 22;
	image.className = 'mw-toolbar-editbutton';
	if ( item.imageId ) {
		image.id = item.imageId;
	}
	image.src = item.imageFile;
	image.border = 0;
	image.alt = item.speedTip;
	image.title = item.speedTip;
	image.style.cursor = 'pointer';
	image.onclick = function() {
		insertTags( item.tagOpen, item.tagClose, item.sampleText );
		// click tracking
		if ( ( typeof $j != 'undefined' )  && ( typeof $j.trackAction != 'undefined' ) ) {
			$j.trackAction( 'oldedit.' + item.speedTip.replace(/ /g, "-") );
		}
		return false;
	};

	parent.appendChild( image );
	return true;
}

// apply tagOpen/tagClose to selection in textarea,
// use sampleText instead of selection if there is none
function insertTags( tagOpen, tagClose, sampleText ) {
	if ( typeof $j != 'undefined' && typeof $j.fn.textSelection != 'undefined' &&
			( currentFocused.nodeName.toLowerCase() == 'iframe' || currentFocused.id == 'wpTextbox1' ) ) {
		$j( '#wpTextbox1' ).textSelection(
			'encapsulateSelection', { 'pre': tagOpen, 'peri': sampleText, 'post': tagClose }
		);
		return;
	}
	var txtarea;
		txtarea = currentFocused;
	var selText, isSample = false;

	if ( document.selection  && document.selection.createRange ) { // IE/Opera
		// save window scroll position
		if ( document.documentElement && document.documentElement.scrollTop ) {
			var winScroll = document.documentElement.scrollTop
		} else if ( document.body ) {
			var winScroll = document.body.scrollTop;
		}
		// get current selection
		txtarea.focus();
		var range = document.selection.createRange();
		selText = range.text;
		// insert tags
		checkSelectedText();
		range.text = tagOpen + selText + tagClose;
		// mark sample text as selected
		if ( isSample && range.moveStart ) {
			if ( window.opera ) {
				tagClose = tagClose.replace(/\n/g,'');
			}
			range.moveStart('character', - tagClose.length - selText.length);
			range.moveEnd('character', - tagClose.length);
		}
		range.select();
		// restore window scroll position
		if ( document.documentElement && document.documentElement.scrollTop ) {
			document.documentElement.scrollTop = winScroll;
		} else if ( document.body ) {
			document.body.scrollTop = winScroll;
		}

	} else if ( txtarea.selectionStart || txtarea.selectionStart == '0' ) { // Mozilla
		// save textarea scroll position
		var textScroll = txtarea.scrollTop;
		// get current selection
		txtarea.focus();
		var startPos = txtarea.selectionStart;
		var endPos = txtarea.selectionEnd;
		selText = txtarea.value.substring( startPos, endPos );
		// insert tags
		checkSelectedText();
		txtarea.value = txtarea.value.substring(0, startPos)
			+ tagOpen + selText + tagClose
			+ txtarea.value.substring(endPos, txtarea.value.length);
		// set new selection
		if ( isSample ) {
			txtarea.selectionStart = startPos + tagOpen.length;
			txtarea.selectionEnd = startPos + tagOpen.length + selText.length;
		} else {
			txtarea.selectionStart = startPos + tagOpen.length + selText.length + tagClose.length;
			txtarea.selectionEnd = txtarea.selectionStart;
		}
		// restore textarea scroll position
		txtarea.scrollTop = textScroll;
	}

	function checkSelectedText() {
		if ( !selText ) {
			selText = sampleText;
			isSample = true;
		} else if ( selText.charAt(selText.length - 1) == ' ' ) { // exclude ending space char
			selText = selText.substring(0, selText.length - 1);
			tagClose += ' ';
		}
	}

}

/**
 * Restore the edit box scroll state following a preview operation,
 * and set up a form submission handler to remember this state
 */
function scrollEditBox() {
	var editBox = document.getElementById( 'wpTextbox1' );
	var scrollTop = document.getElementById( 'wpScrolltop' );
	var editForm = document.getElementById( 'editform' );
	if( editForm && editBox && scrollTop ) {
		if( scrollTop.value ) {
			editBox.scrollTop = scrollTop.value;
		}
		addHandler( editForm, 'submit', function() {
			scrollTop.value = editBox.scrollTop;
		} );
	}
}
hookEvent( 'load', scrollEditBox );
// hookEvent( 'load', mwSetupToolbar );
hookEvent( 'load', function() {
	currentFocused = document.getElementById( 'wpTextbox1' );
	function onfocus( e ) {
		var elm = e.target || e.srcElement;
		if ( !elm ) {
			return;
		}
		var tagName = elm.tagName.toLowerCase();
		var type = elm.type || '';
		if ( tagName !== 'textarea' && tagName !== 'input' ) {
			return;
		}
		if ( tagName === 'input' && type.toLowerCase() !== 'text' ) {
			return;
		}

		currentFocused = elm;
	}
	jQuery('textarea').focus(onfocus);
	
	// HACK: make currentFocused work with the usability iframe
	// With proper focus detection support (HTML 5!) this'll be much cleaner
	if ( typeof $j != 'undefined' ) {
		var iframe = $j( '.wikiEditor-ui-text iframe' );
		if ( iframe.length > 0 ) {
			$j( iframe.get( 0 ).contentWindow.document )
				.add( iframe.get( 0 ).contentWindow.document.body ) // for IE
				.focus( function() { currentFocused = iframe.get( 0 ); } );
		}
	}

} );

// See: http://en.wikipedia.org/wiki/MediaWiki:Common.js/edit.js
// See: getEditToolbar() in /mediawiki/includes/EditPage.php      
mwEditButtons.push({
    "imageFile": "../shared/images/button_bold.png",
    "speedTip": "Bold text",
    "tagOpen": "'''",
    "tagClose": "'''",
    "sampleText": "Bold text"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_italic.png",
    "speedTip": "Italic text",
    "tagOpen": "''",
    "tagClose": "''",
    "sampleText": "Italic text"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_link.png",
    "speedTip": "Internal link",
    "tagOpen": "[[",
    "tagClose": "]]",
    "sampleText": "Link title"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_extlink.png",
    "speedTip": "External link (remember http:// prefix)",
    "tagOpen": "[",
    "tagClose": "]",
    "sampleText": "http://www.example.com link title"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_headline.png",
    "speedTip": "Level 2 headline",
    "tagOpen": "\n== ",
    "tagClose": " ==\n",
    "sampleText": "Headline text"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_image.png",
    "speedTip": "Embedded file",
    "tagOpen": "[[File:",
    "tagClose": "]]",
    "sampleText": "Example.jpg"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_media.png",
    "speedTip": "File link",
    "tagOpen": "[[Media:",
    "tagClose": "]]",
    "sampleText": "Example.ogg"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_math.png",
    "speedTip": "Mathematical formula (LaTeX)",
    "tagOpen": "<math>",
    "tagClose": "</math>",
    "sampleText": "Insert formula here"
});
 mwEditButtons.push({
    "imageFile": "../shared/images/button_nowiki.png",
    "speedTip": "Ignore wiki formatting",
    "tagOpen": "<nowiki>",
    "tagClose": "</nowiki>",
    "sampleText": "Insert non-formatted text here"
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_sig.png",
    "speedTip": "Your signature with timestamp",
    "tagOpen": "--~~~~",
    "tagClose": "",
    "sampleText": ""
});
mwEditButtons.push({
    "imageFile": "../shared/images/button_hr.png",
    "speedTip": "Horizontal line (use sparingly)",
    "tagOpen": "\n----\n",
    "tagClose": "",
    "sampleText": ""
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_strike.png",
    "speedTip": "Strike",
    "tagOpen": "<s>",
    "tagClose": "</s>",
    "sampleText": "Strike-through text"
});
mwCustomEditButtons.push({
     "imageFile": "../shared/images/Button_enter.png",
    "speedTip": "Line break",
    "tagOpen": "<br />",
    "tagClose": "",
    "sampleText": ""
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_upper_letter.png",
    "speedTip": "Superscript",
    "tagOpen": "<sup>",
    "tagClose": "</sup>",
    "sampleText": "Superscript text"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_lower_letter.png",
    "speedTip": "Subscript",
    "tagOpen": "<sub>",
    "tagClose": "</sub>",
    "sampleText": "Subscript text"
});

mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_small.png",
    "speedTip": "Small",
    "tagOpen": "<small>",
    "tagClose": "</small>",
    "sampleText": "Small Text"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_hide_comment.png",
    "speedTip": "Insert hidden Comment",
    "tagOpen": "<!-- ",
    "tagClose": " -->",
    "sampleText": "Comment"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_gallery.png",
    "speedTip": "Insert a picture gallery",
    "tagOpen": "\n<gallery>\n",
    "tagClose": "\n</gallery>",
    "sampleText": "Image:Example.jpg|Caption1\nImage:Example.jpg|Caption2"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_blockquote.png",
    "speedTip": "Insert block of quoted text",
    "tagOpen": "<blockquote>\n",
    "tagClose": "\n</blockquote>",
    "sampleText": "Block quote"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_insert_table.png",
    "speedTip": "Insert a table",
    "tagOpen": '{| class="wikitable"\n|',
    "tagClose": "\n|}",
    "sampleText": "-\n! header 1\n! header 2\n! header 3\n|-\n| row 1, cell 1\n| row 1, cell 2\n| row 1, cell 3\n|-\n| row 2, cell 1\n| row 2, cell 2\n| row 2, cell 3"
});
mwCustomEditButtons.push({
    "imageFile": "../shared/images/Button_reflink.png",
    "speedTip": "Insert a reference",
    "tagOpen": "<ref>",
    "tagClose": "</ref>",
    "sampleText": "Insert footnote text here"
});