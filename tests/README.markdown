Testing Scripto
=============

Scripto uses the SimpleTest PHP testing framework. By running these tests, you 
can:

* Test your external system's adapter for expected results; 
* Test the your MediaWiki instance via Scripto's MediaWiki API client;
* Test the Scripto_Document base class.

Installation
-------------

* Download the [SimpleTest](http://www.simpletest.org/) framework;
* Copy config.php.changeme to config.php;
* Set the configuration in config.php;

To copy the configuration file:

    $ cd /path/to/scripto/tests/
    $ cp config.php.changeme config.php

If you're using the Example adapter, here's a value that will work for the document id: (Look inside lib/Scripto/Adapter/Example.php for why.)

	// Test document ID.
	define('TEST_DOCUMENT_ID', '16344');

Running the Tests
-------------

On the command line: 

    $ cd /path/to/scripto/tests/
    $ php all_tests.php

In the browser:

* Make sure the Scripto tests directory is available to your web server;
* Go to http://your-domain/tests/all_tests.php
