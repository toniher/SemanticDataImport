# SemanticDataImport

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
	define("NS_SDImport", 2000);
	$wgExtraNamespaces[NS_SDImport] = "SDImport";
	$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_SDImport] = true;
```

