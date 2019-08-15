<?php

# Avoids illegal processing, doesn't cost much, but unnecessary on a correct installation
if (!defined('MEDIAWIKI')) { die(-1); } 

call_user_func( function () {

	# Extension Declaration
	$GLOBALS['wgExtensionCredits']['semantic'][] = array(
			'path' => __FILE__,
			'name' => 'Semantic Data Import',
			'author' => array('Toni Hermoso'),
			'version' => '0.4.0',
			'url' => 'https://github.com/toniher/SemanticDataImport',
			'descriptionmsg' => 'sdimport-desc'
	);
	
	$GLOBALS['wgMessagesDirs']['SemanticDataImport'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['SemanticDataImport'] = __DIR__ . '/SemanticDataImport.i18n.php';
	$GLOBALS['wgExtensionMessagesFiles']['SemanticDataImportMagic'] = __DIR__ . '/SemanticDataImport.i18n.magic.php';
	$GLOBALS['wgAutoloadClasses']['SDImportData'] = __DIR__ . '/includes/SDImportData.php';
	$GLOBALS['wgAutoloadClasses']['SDImportDataParser'] = __DIR__ . '/includes/SDImportData.parser.php';
	$GLOBALS['wgAutoloadClasses']['SDImportApi'] = __DIR__ . '/includes/SDImportApi.php';
	$GLOBALS['wgAutoloadClasses']['SDImportJob'] = __DIR__ . '/includes/SDImportJob.php';

	$GLOBALS['wgAPIModules']['sdimport'] = 'SDImportApi';

	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SDImportParserFunction';
	$GLOBALS['wgHooks']['PageContentSaveComplete'][] = 'SDImportData::saveJSONData';

    $GLOBALS['wgAutoloadClasses']['SpecialSDImport'] = __DIR__ . '/includes/SDImport.special.php';

    # SpecialPage referencing
    $GLOBALS['wgSpecialPages']['SDImport'] = 'SpecialSDImport';

	$GLOBALS['wgResourceModules']['ext.sdimport'] = array(
		'localBasePath' => dirname( __FILE__ ),
		'scripts' => array( 'libs/handsontable/handsontable.full.js', 'libs/sdimport.js', 'libs/sdimport.special.js' , 'libs/jquery.csv.min.js'),
		'styles' => array( 'libs/handsontable/handsontable.full.css', 'libs/sdimport.less', 'libs/sdimport.special.css' ),
		'dependencies' => array(
			'mediawiki.jqueryMsg',
		),
		'messages' => array(
			'sdimport-commit',
			'sdimport-form-submit-success',
			'sdimport-form-back',
			'sdimport-form-edit-rowobject-label',
			'sdimport-form-edit-rowobject-button',
			'sdimport-form-empty-file',
			'sdimport-form-error',
			'sdimport-form-restrict-extensions'
		),
		'remoteExtPath' => 'SemanticDataImport'
	);
	
	$GLOBALS['wgResourceModules']['ext.sdimport.form'] = array(
		'localBasePath' => dirname( __FILE__ ),
		'scripts' => array( 'libs/survey-jquery/survey.jquery.js' ),
		'styles' => array( 'libs/survey-jquery/survey.css' ),
		'dependencies' => array(
			'mediawiki.jqueryMsg',
		),
		'remoteExtPath' => 'SemanticDataImport'
	);
	
	# Loading for domains which are JSON-based
	$GLOBALS['wgHooks']['OutputPageBeforeHTML'][] = 'SDImportData::onOutputPageBeforeHTML';
	
	# Export Vars into JS
	$GLOBALS['wgHooks']['ResourceLoaderGetConfigVars'][] = 'SDImportData::onResourceLoaderGetConfigVars';
	
	
	$GLOBALS['$wgSDImportDataPageFileLimitSize'] = '1073741824';
	
	# Example NS definition
	#define("NS_SDImport", 2000);
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport] = array();
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["edit"] = false;
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["editfields"] = false;
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["separator"] = "\t";
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["delimiter"] = '"';
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["rowobject"] = NS_SDImport;
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["rowfields"] = array("Page1", "Page2");
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["typefields"] = array("Page", "Page");
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["ref"] = array("ref" => "{{PAGENAME}}");
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["prefields"] = array( "", "" );
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["postfields"] = array( "", "" );
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["json"] = false; # Whether content is stored directly in JSON
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["single"] = false; # Whether to store straight properties-values, but not Subobject (rowobject is ignored)
	#$GLOBALS["wgSDImportDataPage"][NS_SDImport]["form"] = false; # Whether to use form instead of spreadsheet (only when single)
	#$wgExtraNamespaces[NS_SDImport] = "SDImport";
	#$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_SDImport] = true;

} );


// Hook our callback function into the parser
function SDImportParserFunction( $parser ) {

	$parser->setHook( 'smwdata', 'SDImportDataParser::processData' );
	$parser->setFunctionHook( 'smwdatalink', 'SDImportDataParser::prepareLink', SFH_OBJECT_ARGS );

	return true;
}
