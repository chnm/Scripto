Scripto
=============

&copy; 2010-2011, Center for History and New Media  
License: [GNU GPL v3](http://www.gnu.org/licenses/gpl-3.0.txt)

Scripto is an open source documentary transcription tool written in PHP. It 
features a lightweight library that interfaces MediaWiki and potentially any 
content management system that serves document images. MediaWiki is a good 
choice for the transcription database for several reasons:

* It is the most popular wiki application and had a sizable and active developer 
  community;
* Wiki markup is relatively easy to learn and there are useful WYSIWYG editors 
  available;
* It offers helpful features, such as discussion pages and user administration;
* It comes with a powerful, fully-featured API.

Requirements
-------------

* PHP 5.2.4+
* Zend Framework 1.10+
* MediaWiki 1.15.4+
* Custom adapter interface to (and possibly an API for) the external CMS

Installation
-------------

* Download and install [MediaWiki](http://www.mediawiki.org/wiki/MediaWiki);
* Download the [Zend Framework](http://framework.zend.com/) library;
* Download the [Scripto](https://github.com/chnm/Scripto) library, set the 
  configuration, and use the Scripto library API to build your documentary 
  transcription application.

Suggested Configuration and Setup
-------------

Here's a basic configuration:

    <?php
    
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
    
    // Set the Document and Adapter objects.
    require_once 'Scripto/Document.php';
    require_once 'Scripto/Adapter/Example.php';
    $doc = new Scripto_Document($_REQUEST['documentId'], 
                                MEDIAWIKI_API_URL, 
                                MEDIAWIKI_DB_NAME, 
                                new Scripto_Adapter_Example);
    
    // Set the current page.
    $doc->setPage($_REQUEST['pageId']);

See the examples/ directory for more suggestions on configuration, setup, 
layout, and styles.

Running the Examples
-------------

The examples/ directory contains three example layouts. The Simple layout is the 
most straightforward; while the SideBySide and TopAndBottom layouts are more 
intricate, using [OpenLayers](http://openlayers.org/) for the document image 
viewer and [jQuery](http://jquery.com/) for a dynamic, AJAX-powered user 
interface. To run the examples, follow these steps:

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

