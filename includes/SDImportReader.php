<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
}

# Spreadsheet reader class
class SDImportReader {
	
	protected $format = "csv";
	protected $separator = "\t";
	protected $delimiter = "\"";
	protected $json = false;
	protected $ns = "";

	public function __construct( $params ) {
		
		if ( array_key_exists( "format", $params ) ) {
			$this->format = $params["format"];
		}
		if ( array_key_exists( "separator", $params ) ) {
			$this->separator = $params["separator"];
		}
		if ( array_key_exists( "delimiter", $params ) ) {
			$this->delimiter = $params["delimiter"];
		}
 		if ( array_key_exists( "json", $params ) ) {
			$this->json = $params["json"];
		}
		if ( array_key_exists( "namespace", $params ) ) {
			$this->ns = $params["namespace"];
		}
	}
	

	public function loadFile( $file ) {
		
		$status = false;
		
		if ( $this->format === "csv" ) {
			
			// Process CSV
			$dataByTitle = array(); // Hash with title key

			if ( ($handle = fopen( $file, "r")) !== false ) {
				
				// TODO: Line limit to 10000
				while ( ( $row = fgetcsv($handle, 10000, $this->separator, $this->delimiter ) ) !== false ) {
					
					$title = array_shift( $row );
					
					if ( array_key_exists( $title, $dataByTitle ) ) {
						array_push( $dataByTitle[ $title ], $row );
					} else {
						$dataByTitle[ $title ] = array( $row );
					}
					
				}
				
				fclose($handle);
			}
			
			foreach ( $dataByTitle as $title => $data ) {
				
				if ( $this->json ) {
					
					$status = SDImportData::importJSON( SDImportData::prepareStructForJSON( $data ), $this->formatTitle( $title, $this->ns ) );

				} else {
				
					$text = "";
					foreach ( $data as $row ) {
				
						$text .= $this->getCSV( $row, $this->separator, $this->delimiter )."\n";
					
					}
					
					$status = SDImportData::importWikiText( $text, $this->formatTitle( $title, $this->ns ) );
				}
			}

		}
		
		// TODO: In the future also XLSX, ODS, etc.
		
		return $status;
		
	}
	
	private function outputCSV( $data, $separator, $delimiter ) {
		$fp = fopen( 'php://output', 'w' );
		fputcsv( $fp, $data, $separator, $delimiter );
		fclose( $fp );
	}
	
	private function getCSV( $data, $separator, $delimiter ) {
		ob_start();
		$this->outputCSV( $data, $separator, $delimiter );
		return ob_get_clean();
	}
	
	private function formatTitle( $title, $ns ) {
		
		if ( ! empty( $ns ) ) {
			$title = $ns.":".$title;
		}
		
		return $title;
	}
	
	
	
}