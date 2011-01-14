Scripto User Interface Examples
=============

The examples/ directory contains three example layouts. The Simple/ layout is 
the most straightforward; while the SideBySide/ and TopAndBottom/ layouts are 
more intricate, using [OpenLayers](http://openlayers.org/) for the document 
image viewer and [jQuery](http://jquery.com/) for a dynamic, AJAX-powered user 
interface.

Running the Examples
-------------

To run the examples, follow these steps:

* Make sure the Scripto directory is accessible to your web server;
* Copy config.php.changeme to config.php:

On the command line:

    $ cd /path/to/mydomain.org/Scripto/examples/shared/
    $ cp config.php.changeme config.php

* Set the configuration in config.php:

Something like this:

    <?php
    
    // Path to directory containing Zend Framework, from root.
    define('ZEND_PATH', '/path/to/zend/library');
    
    // Path to directory containing the Scripto library, from root.
    define('SCRIPTO_PATH', '/path/to/mydomain.org/Scripto/lib');
    
    // URL to the MediaWiki installation API.
    define('MEDIAWIKI_API_URL', 'http://mydomain.org/wiki/api.php');
    
    // Name of the MediaWiki database.
    define('MEDIAWIKI_DB_NAME', 'mediawiki_db');

* Load the following layouts in your web browser:
  * http://mydomain.org/Scripto/examples/Simple/
  * http://mydomain.org/Scripto/examples/TopAndBottom/
  * http://mydomain.org/Scripto/examples/SideBySide/

