<?php

# Avoids illegal processing, doesn't cost much, but unnecessary on a correct installation
if (!defined('MEDIAWIKI')) { die(-1); } 

if ( !defined( 'SMW_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="https://semantic-mediawiki.org/">Semantic MediaWiki</a> installed in order to use Semantic Watchlist.' );
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
	$GLOBALS['wgExtensionMessagesFiles']['SemanticDataImport-magic'] = __DIR__ . '/SemanticDataImport.i18n.magic.php';
	$GLOBALS['wgAutoloadClasses']['SDImportData'] = __DIR__ . '/includes/SDImportData.php';
	
	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SDImportParserFunction';
	
	$wgResourceModules['ext.sdimport'] = array(
		'localBasePath' => dirname( __FILE__ ),
		'scripts' => array( 'libs/jquery-handsontable/jquery.handsontable.full.js', 'libs/sdimport.js',  ),
		'styles' => array( 'libs/jquery-handsontable/jquery.handsontable.full.css', 'libs/sdimport.less' ),
		'remoteExtPath' => 'SemanticDataImport'
	);
} );


// Hook our callback function into the parser
function SDImportParserFunction( $parser ) {

	$parser->setHook( 'smwdata', 'SDImportData::processData' );

	return true;
}
