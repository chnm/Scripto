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