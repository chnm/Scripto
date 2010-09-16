<?php
/**
 * @copyright © 2010, Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

require_once 'Scripto/Adapter/Interface.php';

class Scripto_Adapter_Example implements Scripto_Adapter_Interface
{
    private $_pages = array('1' => 'Cover Page',
                            '2' => 'Introduction: page 1', 
                            '3' => 'Introduction: page 2');
    
    public function documentExists($documentId)
    {
        return true;
    }
    
    public function documentPageExists($documentId, $pageId)
    {
        return array_key_exists($pageId, $this->_pages);
    }
    
    public function getDocumentPages($documentId)
    {
        return $this->_pages;
    }
    
    public function getDocumentPageImageUrl($documentId, $pageId)
    {
        return 'http://upload.wikimedia.org/wikipedia/commons/e/e6/Sargis_Pitsak.jpg';
    }
    
    public function importDocumentPageTranscription($documentId, $pageId, $text)
    {
        return false;
    }
    
    public function importDocumentTranscription($documentId, $text)
    {
        return false;
    }
}