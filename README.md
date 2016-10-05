Scripto
=============

&copy; 2010-2012, Center for History and New Media  
License: [GNU GPL v3](http://www.gnu.org/licenses/gpl-3.0.txt)

Scripto is an open source documentary transcription tool written in PHP. It 
features a lightweight library that interfaces MediaWiki and potentially any 
content management system that serves transcribable resources, including text, 
still image, moving image, and audio files.

Scripto is not a content management system. Scripto is not a graphical user 
interface. Scripto is a software library powered by wiki technology that 
developers can use to integrate a custom transcription GUI into an existing CMS. 
You provide the CMS and GUI; Scripto provides the engine for crowdsourcing the 
transcription of your content.

Why MediaWiki?
-------------

MediaWiki is a good choice for the transcription database for several reasons:

* It is the most popular wiki application and has a sizable and active developer community;
* It offers helpful features, such as talk pages, version history, and user administration;
* [Wiki markup](http://en.wikipedia.org/wiki/Help:Wiki_markup) is easy to learn;
* It comes with a powerful, fully-featured [API](http://www.mediawiki.org/wiki/API).

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
  configuration, and use the API to build your documentary transcription 
  application.

Suggested Configuration and Setup
-------------

Here's a basic configuration:

```php
<?php
// Path to directory containing Zend Framework, from root.
define('ZEND_PATH', '/path/to/ZendFramework/library');

// Path to directory containing the Scripto library, from root.
define('SCRIPTO_PATH', '/path/to/Scripto/lib');

// URL to the MediaWiki installation API.
define('MEDIAWIKI_API_URL', 'http://example.com/mediawiki/api.php');

// Set the include path to Zend and Scripto libraries.
set_include_path(get_include_path() 
               . PATH_SEPARATOR . ZEND_PATH 
               . PATH_SEPARATOR . SCRIPTO_PATH);

// Set the Scripto object by passing the custom adapter object and 
// MediaWiki configuration.
require_once 'Scripto.php';
require_once 'Scripto/Adapter/Example.php';
$scripto = new Scripto(new Scripto_Adapter_Example, 
                       array('api_url' => MEDIAWIKI_API_URL));

// Set the current document object.
$doc = $scripto->getDocument($_REQUEST['documentId']);
  
// Set the current document page.
$doc->setPage($_REQUEST['pageId']);

// Render the transcription or talk page using the $scripto and $doc APIs.
```

See the various implementations of Scripto for more suggestions on configuration, 
setup, layout, and styles.

* [Omeka plugin](https://github.com/omeka/plugin-Scripto)
* [WordPress plugin](https://github.com/chnm/scripto-wordpress-plugin)
* [Drupal module](https://github.com/chnm/scripto-drupal-module)

Advanced Usage
-------------

### Record Client IP Address

Scripto does not record a client's IP address by default. All modifications to 
pages will be set to the IP address of the server running Scripto. To record a 
client's IP address, you'll need to add the following code to MediaWiki's 
LocalSettings.php:

```
$wgSquidServersNoPurge = array('127.0.0.1');
```

Where '127.0.0.1' is the IP address of the server running Scripto.

### Base64 Decoding

Scripto Base64 encodes document and page numbers to prevent incompatible 
MediaWiki title characters. Because of this, corresponding page titles in 
MediaWiki will be unusually named. You may place the following code in 
MediaWiki's LocalSettings.php to make page titles human readable:

```
// Decode the MediaWiki title from Base64.
// http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
$wgHooks['BeforePageDisplay'][] = 'fnScriptoDecodePageTitle';
function fnScriptoDecodePageTitle(&$out, &$sk, $prefix = '.', $delimiter = '.')
{
    $title = strtr($out->getPageTitle(), '-_', '+/');
    if ($prefix != $title[0]) {
        return false;
    }
    $title = array_map('base64_decode', explode($delimiter, ltrim($title, $prefix)));
    $title = 'Document ' . $title[0] . '; Page ' . $title[1];
    $out->setPageTitle($title);
    return false;
}
```

Changelog
-------------

* 1.1
    * Add option to retain specified HTML attributes.
* 1.1.1
    * Fix watch and unwatch pages.
* 1.1.2
    * The /e modifier is deprecated in PHP 5.5.0 and removed in 7.0.0. Use
      preg_replace_callback() instead.
