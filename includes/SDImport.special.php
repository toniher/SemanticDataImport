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
		
		$list_namespaces = array( "" => "" );
		
		foreach ( $keys as $key ) {
			
			$list_namespaces[ $key ] = $key;
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
			)
			

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

		global $wgOut;
		global $wgSDImportDataPage;
		global $wgSDImportDataPageFileLimitSize;
		
		$pathInput =  sys_get_temp_dir(); // TODO: This might change, let's use for now tempdir
		
		# First of all, we check namespaces
		$separator = "\t";
		$delimiter = "\"";
		$jsonContent = false;
		
		if ( $formData['namespace'] ) {
			
			if ( ! empty( $formData['namespace'] ) ) {
				
				if ( array_key_exists( $formData['namespace'], $wgSDImportDataPage ) ) {
					
					$namespace = $wgSDImportDataPage[ $formData['namespace'] ];
					
					if ( array_key_exists( "separator", $namespace ) ) {
						$separator = $namespace["separator"];
					}
					if ( array_key_exists( "delimiter", $namespace ) ) {
						$delimiter = $namespace["delimiter"];
					}
					if ( array_key_exists( "json", $namespace ) ) {
						$jsonContent = $namespace["json"];
					}

				}
			}
		}	
		
		
		if ( $formData['separator'] ) {
			$separator = $formData["separator"];
			if ( $separator === "{TAB}" ) {
				$separator = "\t"; // TODO: To check if to be done in a better way
			}
		}

		if ( $formData['delimiter'] ) {
			$delimiter = $formData["delimiter"];
		}
		
		if ( $wgSDImportDataPageFileLimitSize ) {
		
			if ( $_FILES['wpfileupload']['size'] > $wgSDImportDataPageFileLimitSize ) {
			
				$kb = $wgSDImportDataPageFileLimitSize/(1024*1024);
			
				return ("Sorry. Files larger than ".$kb." are not allowed." );
			}
		
		}
		
		if ( $_FILES['wpfileupload']['error'] == 0 ) {
		
			$dt = new DateTime();
			$md5sum = md5($_FILES['wpfileupload']['tmp_name'].$dt->format('U') );
			$pathtempfile = $pathInput."/".$md5sum;
			
			if (!file_exists($pathInput)) {
				mkdir($pathInput, 0755, true);
			}

			if ( move_uploaded_file($_FILES["wpfileupload"]["tmp_name"], $pathtempfile) ) {

				$params = array();
				$params["format"] = "csv";
				$params["separator"] = $separator;
				$params["delimiter"] = $delimiter;
				$params["json"] = $jsonContent;
				$params["namespace"] = $formData['namespace'];
				
				$reader = new SDImportReader( $params );
				$status = $reader->loadFile( $pathtempfile );


				return 'Done';
			
			}
		
		}
	}
	
}
