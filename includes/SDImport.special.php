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
	
	
	// Count BioRound -> for templates -> should be changes for hash
	#	private static $countBioRound = array();
	
	/**
	 * Special page entry point
	 */
	public function execute($par) {
		global $wgOut;
		
		global $wgSDImportDataPage; // Configuration options
	
		$wgOut->addModules( 'ext.sdimport' );
		$this->setHeaders();
	
		// TODO: We should handle request in the form in a better way. $wgRequest Check examples here: involved http://www.mediawiki.org/wiki/Category:Special_page_extensions

	
		# A formDescriptor for uploading stuff
		$formDescriptor = array(
	
			'fileupload' => array(
				'section' => 'upload',
				'label' => 'Upload file', # What's the label of the field
				'type' => 'file'
			),
			'separator' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Separator',
				'options' => array( "{TAB}", ",", ";" )
			),
			'delimiter' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Delimiter',
				'options' => array( "\"", "'" )
			),
			'namespace' => array(
				'section' => 'upload',
				'type' => 'select',
				'label' => 'Namespace',
				'options' => array()
			)
			

		);
	
		$htmlForm = new HTMLForm( $formDescriptor, 'sdimport_form' );
	
		$htmlForm->setSubmitText( wfMessage('sdimport_form-submit-button')->text() ); # What text does the submit button display
		$htmlForm->setTitle( $this->getTitle() ); # You must call setTitle() on an HTMLForm
	
		/* We set a callback function */
		$htmlForm->setSubmitCallback( array( 'SpecialSDImport', 'processCSV' ) );  # Call processInput() in SpecialAnnoWiki on submit
	
		$htmlForm->suppressReset(false); # Get back reset button
	
		$wgOut->addHTML( "<div id='sdform' class='sdimport_section'>" );
		$htmlForm->show(); # Displaying the form
		$wgOut->addHTML( "</div>" );
		$wgOut->addHTML( "<div id='sdpreview' class='sdimport_section'></div>" );
	
	}

	
}
