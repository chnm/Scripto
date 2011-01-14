<?php
require('../shared/config.php');

// Must set the Content-Type header to correctly display UTF-8.
header('Content-Type: text/html; charset=utf-8');

// Get the document ID and page ID.
$documentId = isset($_GET['documentId']) ? $_GET['documentId'] : 16344;
$pageId = isset($_GET['pageId']) ? $_GET['pageId'] : null;

// Set the Adapter object.
require_once 'Scripto/Adapter/Example.php';
$adapter = new Scripto_Adapter_Example;

// Set the Document object.
require_once 'Scripto/Document.php';
$doc = new Scripto_Document($documentId, 
                            MEDIAWIKI_API_URL, 
                            MEDIAWIKI_DB_NAME, 
                            $adapter);

// Must set the current page first.
$doc->setPage($pageId);

//var_dump($doc->getBaseTitle());
//var_dump($doc->decodeBaseTitle($doc->getBaseTitle()));

if (isset($_POST['submit_login'])) {
    $doc->login($_POST['username'], $_POST['password']);
}

if (isset($_POST['submit_logout'])) {
    $doc->logout();
}

// Determine if the current user can edit MediaWiki.
$canEdit = $doc->canEdit();

if ($canEdit) {
    // Edit the transcription if submitted.
    if (isset($_POST['submit_transcription'])) {
        $doc->editTranscriptionPage($_POST['transcription']);
}
    if (isset($_POST['submit_talk'])) {
        $doc->editTalkPage($_POST['talk']);		
    }

    // Set up the OpenLayers image viewer.
    $olExternalGraphicUrl = $doc->getPageImageUrl();
    $size = getimagesize($olExternalGraphicUrl);
    $olGraphicWidth = 400;
    $olGraphicHeight = (400 * $size[1]) / $size[0];
	$olMapHeight = 250;
	$olMapWidth = 700;
    
    // Set up the MediaWiki edit toolbar.
    $mwUrl = dirname(MEDIAWIKI_API_URL);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scripto Example</title>
    <!--link rel="stylesheet" href="screen.css" /-->
	<link rel="stylesheet" href="screen.css" />
    <?php if ($canEdit): // Include the necessary scripts if the user can edit. ?>
    <script src="../shared/jquery-1.4.2.min.js" type="text/javascript"></script>
	<script src="../shared/jquery-ui-1.8.5.js" type="text/javascript"></script>
	<script src="../shared/jquery.form.js" type="text/javascript"></script>
	<script type="text/javascript">
		jQuery.noConflict();
	</script>
    <style type="text/css"><?php include '../shared/imageViewer.css.php'; ?></style>
    <script src="../shared/OpenLayers.ScriptoFork.js" type="text/javascript"></script>
    <script type="text/javascript"><?php  include '../shared/imageViewer.js.php'; ?></script>
    <script src="../shared/MediaWikiToolbar.js" type="text/javascript"></script>
	<script>
	  jQuery(document).ready(function() {
	    jQuery("#transcriptionWrap").tabs();

            // bind 'myForm' and provide a simple callback function 
            jQuery('#transcriptionEditForm').ajaxForm(function() {
                jQuery.get('../shared/ajax.php', {documentId:<?php echo $doc->getId(); ?>, pageId:<?php echo $doc->getPageId(); ?>, type:'transcription'}, function(data){
                    jQuery('#transcriptionCurrent').html(data);
                });
                return false; 
            }); 
			jQuery('#talkEditForm').ajaxForm(function() { 
			    jQuery.get('../shared/ajax.php', {documentId:<?php echo $doc->getId(); ?>, pageId:<?php echo $doc->getPageId(); ?>, type:'talk'}, function(data){
                    jQuery('#discussionCurrent').html(data);
                 });   
                return false; 
            });
			jQuery('.toolbar').each(function() {
				setupToolbar(this);
				});
				
        }); 
    </script>

<?php endif; ?>
</head>
<body <?php echo $canEdit ? 'onload="init()"' : ''; ?>>
<div id="wrap">
	
    <h1>Scripto Example</h1>
    <?php if (!$canEdit): // Display the login form if the current user does not have permission to edit. ?>
    <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
        <p>Username: <input type="input" name="username" /></p>
        <p>Password: <input type="password" name="password" /></p>
        <p><input type="submit" name="submit_login" value="Login" /></p>
    </form>
    <?php else: // Display the edit and logout forms and/or process the edit form if the current user has permission to edit. ?>
		
	<div id="documentContainer">

    <div id="mapContainer">
        <button onmousedown="rotate(-1, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate ←</button>
        <button onmousedown="rotate(1, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate →</button>
        <button onclick="rotateGraphic(90)">Rotate 90°</button>
        <button onclick="rotateGraphic(0)">Reset</button>
        <div id="olMap"></div>
    </div>

	<div id="transcriptionWrap">

    <div id="transcriptionCurrent">
		<?php echo $doc->getTranscriptionPageHtml(); ?>
    </div>

	<div id="transcriptionEdit">
		<div class="toolbar"><!-- MediaWiki edit toolbar --></div>
        <form id="transcriptionEditForm" action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <textarea name="transcription" id="wpTextbox1" rows="4" cols="80"><?php echo $doc->getTranscriptionPageWikitext(); ?></textarea><br />
            <input type="submit" name="submit_transcription" value="Submit" />
        </form>
	</div>
	
	<div id="discussionCurrent">
		<?php echo $doc->getTalkPageHtml(); ?>
	</div>

	<div id="discussionEdit">
		<div class="toolbar"><!-- MediaWiki edit toolbar --></div>
        <form id="talkEditForm" action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <textarea name="talk" id="wpTextbox2" rows="4" cols="80"><?php echo $doc->getTalkPageWikitext(); ?></textarea><br />
            <input type="submit" name="submit_talk" value="Submit" />
        </form>
	</div>
	
	<ul>
		<li><a href="#transcriptionCurrent"><span>Transcription Current</span></a></li>
		<li><a href="#transcriptionEdit"><span>Transcription Edit</span></a></li>
		<li><a href="#discussionCurrent"><span>Discussion Current</span></a></li>
		<li><a href="#discussionEdit"><span>Discussion Edit</span></a></li>
	</ul>
	
	</div><!-- end transcriptionWrap -->
	
	<div id="logOut">
		<form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <input type="submit" name="submit_logout" value="Logout" />
        </form>
	</div>
	
	</div>
		
	<div id="documentPages">
		<?php endif; ?>
	    <?php foreach ($doc->getPages() as $pageId => $pageName): ?>
	    <a href="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($pageId); ?>"><?php echo $pageName; ?></a><br />
	    <?php endforeach; ?>
	</div>

</div><!--end wrap-->
</body>
</html>