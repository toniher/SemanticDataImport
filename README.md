# SemanticDataImport

[![Build Status](https://secure.travis-ci.org/toniher/SemanticDataImport.svg?branch=master)](http://travis-ci.org/toniher/SemanticDataImport)
[![Code Coverage](https://scrutinizer-ci.com/g/toniher/SemanticDataImport/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/toniher/SemanticDataImport/?branch=master)

Extension for importing CSV-like structured data into MediaWiki pages using [Semantic MediaWiki](https://www.semantic-mediawiki.org).

It basically turns CSV rows into [Semantic Subobjects](https://www.semantic-mediawiki.org/wiki/Subobject).

## Basic Usage

Content can be saved straight into wikitext pages (only option for older wikis) or also as pure JSON pages (only via SpecialPage interface ```Special:SDImport```).

## Namespace configuration

At the time of writting, mappings of CSV columns against Semantic MediaWiki properties can only be done by configuring (custom or not) namespaces in ```LocalSettings.php```.  

```php
	# Example NS definition
	$GLOBALS["wgSDImportDataPage"]["SDImport"] = array();
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["edit"] = false;
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["separator"] = "\t";
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["delimiter"] = '"';
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["rowobject"] = "SDImport";
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["rowfields"] = array("Page1", "Page2");
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["typefields"] = array("Page", "Page");
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["ref"] = array("ref" => "{{PAGENAME}}");
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["prefields"] = array( "", "" );
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["postfields"] = array( "", "" );
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["json"] = false; # Whether content is stored directly in JSON
	$GLOBALS["wgSDImportDataPage"]["SDImport"]["single"] = false; #Whether to store straight properties-values, but not Subobject (rowobject is ignored)
	define("NS_SDImport", 2000);
	$wgExtraNamespaces[NS_SDImport] = "SDImport";
	$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_SDImport] = true;
```

Canonical namespaces are used. For Main namespace, we use "_" instead;

## SDImport interface

There is a preliminary SDImport Special Page (```Special:SDImport```) that simplifies uploading content (especially for JSON pages)

Properties need to be defined in namespace configuration.

At the time of writing:

* First column: Page name (in selected namespace)
* Rest of the columns, according to rowfields values...


## JSON schema

Keys defined in JSON schema have precedence in front of what is defined in namespace configuration.

```json
	{
		"meta": {
			"app": "SDI",
			"version": 0.1,
			"rowobject": "Entry",
			"rowfields": ["Relation1", "Relation2"]
		},
		"data": [
			[
				"2",
				"4"
			],
			[
				"2",
				"5"
			]
		]
	}
```

## Useful extensions

If we enable ```$GLOBALS["wgSDImportDataPage"]["SDImport"]["edit"] = true;``` in LocalSettings.php we allow content to be modified by a spreadsheet-like interface.

However we might be interested to edit in another way. For this we recommend to install 2 extensions:

* [WikiEditor](https://www.mediawiki.org/wiki/Extension:WikiEditor)
* [CodeEditor](https://www.mediawiki.org/wiki/Extension:CodeEditor)


```$wgDefaultUserOptions['usebetatoolbar'] = 1; // user option provided by WikiEditor extension```
