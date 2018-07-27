<?php
class SDImportApi extends ApiBase {

	public function execute() {

		$params = $this->extractRequestParams();

		$jsonmodel = false;
		
		if ( array_key_exists( "model", $params ) ) {
		
			if ( $params["model"] === "json" ) {
				$jsonmodel = true;
			}
					
		}

		//wfDebugLog( "sdimport", json_encode( $params ) );

		if ( $jsonmodel ) {
			//wfDebugLog( "sdimport", "Batch: ".$params['model'] );
			if ( $params["batch"] === true ) {
				$status = SDImportData::importJSONBatch( $params['text'], $params['title'], $params['overwrite'] );
			}
			else {
				// wfDebugLog( "sdimport", "Hereeeee" );
				$status = SDImportData::importJSON( $params['text'], $params['title'], $params['overwrite'] );
			}
		}
		else {
			$status = SDImportData::importWikiText( $params['text'], $params['title'], $params['separator'], $params['delimiter'], $params['num'] );
		}
		
		$this->getResult()->addValue( null, $this->getModuleName(), array ( 'status' => $status ) );

		return true;

	}
	public function getAllowedParams() {
		return array(
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'separator' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'delimiter' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'num' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'model' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'overwrite' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_REQUIRED => false
			),
			'batch' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_REQUIRED => false
			)
		);
	}

	public function getDescription() {
		return array(
			'API for importing data into smwdata page'
		);
	}
	public function getParamDescription() {
		return array(
			'text' => 'Content to be processed'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 1.1';
	}
}
