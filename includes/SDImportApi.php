<?php
class SDImportApi extends ApiBase {

	public function execute() {

		$params = $this->extractRequestParams();

		$jsonmodel = false;
		
		if ( array_key_exists( "model", $params ) ) {
		
			if ( $params["mode"] === "json" ) {
				$jsonmodel = true;
			}
					
		}
		
		if ( $jsonmodel ) {
			$status = SDImportData::importJSON( $params['text'], $params['title'], $params['overwrite'] );
		} else {
			$status = SDImportData::importConf( $params['text'], $params['title'], $params['separator'], $params['delimiter'], $params['num'] );
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
				ApiBase::PARAM_REQUIRED => true
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
