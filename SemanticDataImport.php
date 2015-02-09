<?php

# Avoids illegal processing, doesn't cost much, but unnecessary on a correct installation
if (!defined('MEDIAWIKI')) { die(-1); } 

if ( !defined( 'SMW_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="https://semantic-mediawiki.org/">Semantic MediaWiki</a> installed in order to use Semantic Data Import.' );
}
if ( version_compare( SMW_VERSION, '1.9', '<' ) ) {
	die( '<b>Error:</b> Semantic Data Import requires Semantic MediaWiki 1.9 or above.' );
}

call_user_func( function () {

	# Extension Declaration
	$GLOBALS['wgExtensionCredits']['semantic'][] = array(
			'path' => __FILE__,
			'name' => 'Semantic Data Import',
			'author' => array('Toni Hermoso'),
			'version' => '0.1',
			'url' => 'https://github.com/toniher/SemanticDataImport',
			'descriptionmsg' => 'sdimport-desc'
	);
	
	$GLOBALS['wgMessagesDirs']['SemanticDataImport'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['SemanticDataImport'] = __DIR__ . '/SemanticDataImport.i18n.php';
	$GLOBALS['wgAutoloadClasses']['SDImportData'] = __DIR__ . '/includes/SDImportData.php';
	
	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SDImportParserFunction';
	
	$GLOBALS['wgResourceModules']['ext.sdimport'] = array(
		'localBasePath' => dirname( __FILE__ ),
		'scripts' => array( 'libs/jquery-handsontable/jquery.handsontable.full.js', 'libs/sdimport.js',  ),
		'styles' => array( 'libs/jquery-handsontable/jquery.handsontable.full.css', 'libs/sdimport.less' ),
		'remoteExtPath' => 'SemanticDataImport'
	);
	
	# Example NS definition
	#$GLOBALS["$GLOBALS["wgSDImportDataPage"]"]["SDImport"] = array();
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["edit"] = false;
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["separator"] = "\t";
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["delimiter"] = '"';
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["rowobject"] = "SDImport";
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["rowfields"] = array("Page1", "Page2");
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["typefields"] = array("Page", "Page");
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["ref"] = array("ref" => "{{PAGENAME}}");
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["prefields"] = array( "", "" );
	#$GLOBALS["wgSDImportDataPage"]["SDImport"]["postfields"] = array( "", "" );
	#define("NS_SDImport", 2000);
	#$wgExtraNamespaces[NS_SDImport] = "SDImport";
	#$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_SDImport] = true;

} );


// Hook our callback function into the parser
function SDImportParserFunction( $parser ) {

	$parser->setHook( 'smwdata', 'SDImportData::processData' );

	return true;
}
