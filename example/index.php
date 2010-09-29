<?php
ini_set('display_errors', 1);

// Path to directory containing Zend Framework, from root.
define('ZEND_PATH', '');

// Path to directory containing the Scripto library, from root.
define('SCRIPTO_PATH', '');

// URL to the MediaWiki installation API.
define('MEDIAWIKI_API_URL', '');

// Name of the MediaWiki database.
define('MEDIAWIKI_DB_NAME', '');

// Set the include path to Zend and Scripto libraries.
set_include_path(get_include_path() 
                 . PATH_SEPARATOR . ZEND_PATH 
                 . PATH_SEPARATOR . SCRIPTO_PATH);

// Must set the Content-Type header to correctly display UTF-8.
header('Content-Type: text/html; charset=utf-8');

// Get the document ID and page ID.
$documentId = isset($_GET['documentId']) ? $_GET['documentId'] : 16344;
$pageId = isset($_GET['pageId']) ? $_GET['pageId'] : 67799;

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

    // Set up the OpenLayers image viewer.
    $olExternalGraphicUrl = $doc->getPageImageUrl();
    $size = getimagesize($olExternalGraphicUrl);
    $olGraphicWidth = 400;
    $olGraphicHeight = (400 * $size[1]) / $size[0];
    $olMapWidth = 600;
    
    // Set up the MediaWiki edit toolbar.
    $mwUrl = dirname(MEDIAWIKI_API_URL);
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Scripto Example</title>
    <link rel="stylesheet" href="screen.css" />
    <?php if ($canEdit): // Include the necessary scripts if the user can edit. ?>
    <style type="text/css"><?php include '../OpenLayers/imageViewer.css.php'; ?></style>
    <script src="../OpenLayers/OpenLayers.ScriptoFork.js" type="text/javascript"></script>
    <script type="text/javascript"><?php  include '../OpenLayers/imageViewer.js.php'; ?></script>
    <script src="MediaWikiToolbar.js" type="text/javascript"></script>
    <script src="<?php echo $mwUrl; ?>/skins/common/edit.js" type="text/javascript"><!-- Core MediaWiki edit toolbar functions --></script>
    <?php endif; ?>
</head>
<body onload="init()">
    <h1>Scripto Example</h1>
    <?php if (!$canEdit): // Display the login form if the current user does not have permission to edit. ?>
    <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
        <p>Username: <input type="input" name="username" /></p>
        <p>Password: <input type="password" name="password" /></p>
        <p><input type="submit" name="submit_login" value="Login" /></p>
    </form>
    <?php else: // Display the edit and logout forms and/or process the edit form if the current user has permission to edit. ?>
    <div id="mapContainer">
        <button onmousedown="rotate(-3, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate ←</button>
        <button onmousedown="rotate(3, 50)" onmouseup="stopRotate()" onmouseout="stopRotate()">Rotate →</button>
        <button onclick="rotateGraphic(90)">Rotate 90°</button>
        <button onclick="rotateGraphic(0)">Reset</button>
        <div id="olMap"></div>
    </div>
    <div id="transcriptionContainer">
        <div id="toolbar"><!-- MediaWiki edit toolbar --></div>
        <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <textarea name="transcription" id="wpTextbox1" rows="24" cols="80"><?php echo $doc->getTranscriptionPageWikitext(); ?></textarea><br />
            <input type="submit" name="submit_transcription" value="Submit" />
        </form>
        <form action="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($doc->getPageId()); ?>" method="post">
            <input type="submit" name="submit_logout" value="Logout" />
        </form>
    <?php endif; ?>
    <?php foreach ($doc->getPages() as $pageId => $pageName): ?>
    <a href="?documentId=<?php echo urlencode($doc->getId()); ?>&amp;pageId=<?php echo urlencode($pageId); ?>"><?php echo $pageName; ?></a><br />
    <?php endforeach; ?>
    <?php echo $doc->getTranscriptionPageHtml(); ?>
    </div>
</body>
</html>