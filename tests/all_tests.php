<?php
// SimpleTest errors without following these instructions: 
// http://sourceforge.net/tracker/index.php?func=detail&aid=3100649&group_id=76550&atid=547455

require_once 'config.php';

class AllTests extends TestSuite {
    public function AllTests() {
        $this->TestSuite('All tests');
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        $this->addFile($path . 'adapter_test.php');
        $this->addFile($path . 'mediawiki_test.php');
        $this->addFile($path . 'scripto_test.php');
        $this->addFile($path . 'document_test.php');
    }
}
