<?php
require('config.php');

// Get the document ID and page ID.
$documentId = isset($_GET['documentId']) ? $_GET['documentId'] : null;
$pageId = isset($_GET['pageId']) ? $_GET['pageId'] : null;
$type = $_GET['type'] == 'talk' ? 'talk' : 'transcription';

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

// Get updated transcription
switch ($type) {
    case 'transcription':
        echo $doc->getTranscriptionPageHtml();
        break;
    case 'talk':
        echo $doc->getTalkPageHtml();
        break;
    default:
        echo '';
        break;
}