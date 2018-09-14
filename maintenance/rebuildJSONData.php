<?php

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
require_once $basePath . '/maintenance/Maintenance.php';


/* 
 * @author Toni Hermoso
 */
class RebuildJSONData extends Maintenance {
		public function __construct() {
		parent::__construct();
		$this->addDescription( "\n" .
			"Script for rebuilding data stored in JSON stores\n"
		);
		$this->addDefaultParams();
	}

		/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {
		$this->addOption( 'namespace', '<namespace> Namespace index number to be refreshed.', true, true, "ns" );
		$this->addOption( 'dryrun', '<dryRun> If you don\'t really want to refresh information', false, false, "dr" );
		$this->addOption( 'u', 'User to run the script', false, true );
	}
	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		
		if ( !defined( 'SMW_VERSION' ) || !$GLOBALS['smwgSemanticsEnabled'] ) {
			
			$this->reportMessage( "\nYou need to have SMW enabled in order to run this maintenance script!\n" );
			return false;
		}
		
		$ns = $this->getOption( "namespace", null ); 
		$dryRun = $this->getOption( "dryrun", false); 
		$u = $this->getOption( 'u', false );

		$reportingInterval = 100;
		$dbr = wfGetDB( DB_SLAVE );
		$ns_restrict = "page_namespace > -1";
		$tables = array('page');
		
		$seltables = array( 'page_id' );
		// Need to do for NS
		if ( $ns > -1 ) {
			if ( is_numeric( $ns ) ) {
				$ns_restrict = "page_namespace = $ns";
			}
		}

		// Default, no user
		$user = null;
		if ( $u ) {
			// $user = User::newSystemUser("SDImport");
			$user = User::newFromName( $u );
		}
		

		$res = $dbr->select( $tables,
			$seltables,
			array(
				$ns_restrict ),
			__METHOD__
		);
		$num = $dbr->numRows( $res );
		$this->output( "$num articles...\n" );
		$i = 0;
		foreach ( $res as $row ) {
			if ( !( ++$i % $reportingInterval ) ) {
				$this->output( "$i\n" );
				wfWaitForSlaves(); // Doubt if necessary
			}
			if ( ! $dryRun ) {
				self::refreshArticle( $row->page_id, $user );
			}
		}

	}
	
	
		/**
	 * Run fixEditFromArticle for all links on a given page_id
	 * @param $id int The page_id
	 */
	public static function refreshArticle( $pageid, $user ) {

		$wikipage = WikiPage::newFromID( $pageid );
	
		if ( $wikipage === null ) {
			return;
		}

		// Check compatibility. Only if newer versions of MW
		if ( method_exists ( $wikipage, "getContent" ) ) {
			$contentModel = $wikipage->getContentModel();
			if ( $contentModel === "json" || ! $wikipage->exists() ) {

				// Retrigger import
				// $title = $wikipage->getTitle()->getPrefixedText();
				
				$statusValue = new StatusValue();
				$statusValue->setOK(true);
				$status = new Status();
				$status->wrap( $statusValue );
//		
//				global $wgRequest;
//				// var_dump( $wgRequest );
//
//
//				$token = $user->getEditToken( '', $wgRequest );
//                $json = $wikipage->getContent()->getNativeData();
//                #$jsonObj = json_decode( $json, true );
//                #$jsonObj["meta"]["xxx"] = "tal";
//                #$json = json_encode( $jsonObj );
//				$apiParams = [
//                                                'action' => 'sdimport',
//                                                'title' => $wikipage->getTitle()->getPrefixedText(),
//                                                'format' => 'json',
//                                                'model' => 'json',
//                                                'overwrite' => true,
//                                                'text' => $json
//				];
//				// var_dump( $wgRequest->getSessionArray() );
//
//				// $apiRequest = new FauxRequest( $apiParams, true, $wgRequest->getSessionArray() );
//				$apiRequest = new DerivativeRequest( $wgRequest, $apiParams, /* $wasPosted = */ true );
//				$apiRequest->setIP( '127.0.0.1' );
//				$context = RequestContext::getMain();
//				$context->setUser( $user );
//				
//				$context->setRequest( $apiRequest );
//				$api = new ApiMain( $context, true );
//				wfRunHooks( 'ApiBeforeMain', array( &$api ) );
//				//var_dump( $api );
//				$api->execute();

				// var_dump( $api->getResult()->getResultData() );

/**
action	sdimport
format	json
model	json
overwrite	true
text	{"meta":{"app":"SDI","version":0.1,"rowfields":["source","type","phase","location","start","end","strand","genome_id","id","ref_id","plain_id","plain_ref_id"],"single":true},"data":[["AUGUSTUS","gene",".","scaffold_34","66623","71635","-","clogmia6","scaffold_34.g18@Genome:clogmia6","Item:Annotation:scaffold_34@Genome:clogmia6","scaffold_34.g18","scaffold_34"]]}
title	Item:Annotation:scaffold+34.g18@Genome:clogmia6

**/

	
                // TODO: To be fixed	
				SDImportData::saveJSONData( $wikipage, $user, $wikipage->getContent(), "Rebuild", 0, null, null, 2, $wikipage->getRevision(), $status, false );
				// $status = $wikipage->doEditContent( $wikipage, "Rebuild", EDIT_FORCE_BOT, false, $user );
				// var_dump( $status );
				// SDImportData::importJSON( $wikipage->getContent()->getNativeData(), $wikipage->getTitle()->getPrefixedText(), true );

			}
		}
		
	}
	
}


$maintClass = 'RebuildJSONData';
require_once( DO_MAINTENANCE );
