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
require_once 'Scripto.php';
$scripto = new Scripto($adapter, array('api_url' => MEDIAWIKI_API_URL, 
                                       'db_name' => MEDIAWIKI_DB_NAME));
$doc = $scripto->getDocument($documentId);

// Must set the current page first.
$doc->setPage($pageId);

//var_dump($doc->getBaseTitle());
//var_dump($doc->decodeBaseTitle($doc->getBaseTitle()));

if (isset($_POST['submit_login'])) {
    $scripto->login($_POST['username'], $_POST['password']);
}

if (isset($_POST['submit_logout'])) {
    $scripto->logout();
}

// Determine if the current user can edit MediaWiki.
$canEditTranscription = $doc->canEditTranscriptionPage();
$canEditTalk = $doc->canEditTalkPage();

if ($canEditTranscription) {
    // Edit the transcription if submitted.
    if (isset($_POST['submit_transcription'])) {
        $doc->editTranscriptionPage($_POST['transcription']);
    }
}

if ($canEditTalk){
    // Edit the talk page if submitted
    if (isset($_POST['submit_talk'])) {
        $doc->editTalkPage($_POST['talk']);		
        }
}


// Set up the OpenLayers image viewer.
$olExternalGraphicUrl = $doc->getPageFileUrl();
$size = getimagesize($olExternalGraphicUrl);
$olGraphicWidth = 400;
$olGraphicHeight = (400 * $size[1]) / $size[0];
$olMapHeight = 400;
$olMapWidth = 460;

// Set up the MediaWiki edit toolbar.
$mwUrl = dirname(MEDIAWIKI_API_URL);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Scripto Example</title>
    <?php if ($canEditTranscription): // Include the necessary scripts if the user can edit. ?>
    <script src="../shared/jquery-1.4.2.min.js" type="text/javascript"></script>
    <script type="text/javascript">
		jQuery.noConflict();
	</script>
    <style type="text/css"><?php include '../shared/imageViewer.css.php'; ?></style>
    <script src="../shared/OpenLayers.ScriptoFork.js" type="text/javascript"></script>
    <script type="text/javascript"><?php  include '../shared/imageViewer.js.php'; ?></script>
    <script src="../shared/MediaWikiToolbar.js" type="text/javascript"></script>
    <script type="text/javascript">
	  jQuery(document).ready(function() {
		jQuery('.toolbar').each(function() {
			setupToolbar(this);
			});			
        }); 
    </script>
    <?php endif; ?>
</head>
<body <?php echo $canEditTranscription ? 'onload="init()"' : ''; ?>>
<div id="wrap">
		
    <h1>Scripto Example</h1>
    <?php if (!$canEditTranscription): // Display the login form if the current user does not have permission to edit. ?>
    <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
        <p>Username: <input type="input" name="username" /></p>
        <p>Password: <input type="password" name="password" /></p>
        <p><input type="submit" name="submit_login" value="Login" /></p>
    </form>
    <?php else: // Display the edit and logout forms and/or process the edit form if the current user has permission to edit. ?>
    <div id="mapContainer">
        <button onmousedown="rotate(-1, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate ←</button>
        <button onmousedown="rotate(1, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate →</button>
        <button onclick="rotateGraphic(90)">Rotate 90°</button>
        <button onclick="rotateGraphic(0)">Reset</button>
        <div id="olMap"></div>
    </div>
    <div id="transcriptionContainer">
		<div class="toolbar"><!-- MediaWiki edit toolbar --></div>
        <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <textarea name="transcription" id="wpTextbox1" rows="24" cols="80"><?php echo $doc->getTranscriptionPageWikitext(); ?></textarea><br />
            <input type="submit" name="submit_transcription" value="Submit" />
        </form>
        <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <input type="submit" name="submit_logout" value="Logout" />
        </form>
        <?php echo $doc->getTranscriptionPageHtml(); ?>
	    
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