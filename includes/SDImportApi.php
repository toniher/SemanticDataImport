<?php
class SDImportApi extends ApiBase {

	public function execute() {

		$params = $this->extractRequestParams();

		$output = SDImportData::importConf( $params['text'], $params['title'], $params['delimiter'], $params['enclosure'] );

		$this->getResult()->addValue( null, $this->getModuleName(), array ( 'status' => $output['status'], 'msg' => $output['msg'] ) );

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
			'delimiter' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'enclosure' => array(
				ApiBase::PARAM_TYPE => 'string',
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