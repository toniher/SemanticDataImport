<?php
if (!defined('MEDIAWIKI')) { die(-1); } 
 
 
# Our SpecialPage
class SpecialSDImport extends SpecialPage {
	
	
	/**
	 * Constructor : initialise object
	 * Get data POSTed through the form and assign them to the object
	 * @param $request WebRequest : data posted.
	 */
	
	public function __construct($request = null) {
		parent::__construct('SDImport');   #The first argument must be the name of your special page
	

	}
	
	private static function getKeyOptionsWithBlank( $keys ) {
		
		global $wgContLang;
		
		$list_namespaces = array( "" => "" );
		
		foreach ( $keys as $key ) {
			
			$keyName = $wgContLang->getNsText( $key );
			
			$list_namespaces[ $keyName ] = $key;
		}
		
		return $list_namespaces;
	}

	
	/**
	 * Special page entry point
	 */
	public function execute($par) {
		global $wgOut;
		
		global $wgSDImportDataPage; // Configuration options
	
		$list_namespaces = self::getKeyOptionsWithBlank( array_keys( $wgSDImportDataPage ) );
	
		$wgOut->addModules( 'ext.sdimport' );
		$this->setHeaders();
	
		// TODO: We should handle request in the form in a better way. $wgRequest Check examples here: involved http://www.mediawiki.org/wiki/Category:Special_page_extensions
	
		# A formDescriptor for uploading stuff
		$formDescriptor = array(
	
			'fileupload' => array(
				'section' => 'upload',
				'label' => 'Upload file',
                'class' => 'HTMLTextField',
				'type' => 'file'
			),
			'separator' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Separator',
				'options' => array(  "," => ",","{TAB}" => "{TAB}", ";" => ";" )
			),
			'delimiter' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Delimiter',
				'options' => array( "\"" => "\"" , "'" => "'" )
			),
			'namespace' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Namespace',
				'options' => $list_namespaces
			),
			'single' => [
				'section' => 'upload',
				'class' => 'HTMLCheckField',
				'label' => 'Single row mode',
				'default' => false,
			]		

		);

	
		$htmlForm = new HTMLForm( $formDescriptor, 'sdimport-form' );
	
		$htmlForm->setSubmitText( wfMessage('sdimport-form-submit-button')->text() ); # What text does the submit button display
		$htmlForm->setTitle( $this->getTitle() ); # You must call setTitle() on an HTMLForm
	
		/* We set a callback function */
		$htmlForm->setSubmitCallback( array( 'SpecialSDImport', 'processCSV' ) );
	
		$htmlForm->suppressReset(false); # Get back reset button
	
		$wgOut->addHTML( "<div id='sdintro' class='sdimport_section'>" );
		$wgOut->addHTML( wfMessage('sdimport-form-intro')->text() );			
		$wgOut->addHTML( "</div>" );
		$wgOut->addHTML( "<div id='sdform' class='sdimport_section'>" );
		$htmlForm->show(); # Displaying the form
		$wgOut->addHTML( "</div>" );
		$wgOut->addHTML( "<div id='sdpreview' class='hot handsontable htColumnHeaders sdimport_section'></div>" );
		//$wgOut->addHTML( "<div id='sdpreview' class='hot handsontable htColumnHeaders'></div>");
	}
	
	
	static function processCSV( $formData ) {

		// Everything handled via Javascript. Nothing done here...
		return true;
	}
	
}
