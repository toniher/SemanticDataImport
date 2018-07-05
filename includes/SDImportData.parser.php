<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
}

/** In this class we store things related to data processing **/

class SDImportDataParser {

	/**
	* @param $input string
	* @param $args array
	* @param $parser Parser
	* @param $frame Frame
	* @return string
	*/
	public static function processData( $input, $args, $parser, $frame ) {
	
		global $wgSDImportDataPage;
		$pageTitle = $parser->getTitle();
		$output = "";

		$separator="\t";
		$delimiter='"';
		$fields = array();

		if ( is_object( $pageTitle ) ) {

			$ns = $pageTitle->getNsText();

			if ( key_exists( $ns, $wgSDImportDataPage ) ) {

				$nsRepo = $wgSDImportDataPage[$ns];
				
				$separator = SDImportData::getSelector( $args, $nsRepo, "separator" ); // String
				$delimiter = SDImportData::getSelector( $args, $nsRepo, "delimiter" ); // String
				$object = SDImportData::getSelector( $args, $nsRepo, "rowobject" ); // String
				$fields = SDImportData::getSelector( $args, $nsRepo, "rowfields" ); // Array
				$props = SDImportData::getSelector( $args, $nsRepo, "typefields" ); // Array
				$refs = SDImportData::getSelector( $args, $nsRepo, "ref" ); // Hash
				$pre = SDImportData::getSelector( $args, $nsRepo, "prefields" ); // Array
				$post = SDImportData::getSelector( $args, $nsRepo, "postfields" ); // Array
				$editable = SDImportData::getSelector( $args, $nsRepo, "edit" ); // Boolean
				// TODO: Add single option here maybe?

				// TODO: Should we add props here if they don't exist?
				

				$dprops = array();

				if ( $refs ) {
					foreach ( $refs as $key => $val ) {
						$dprops[ $key ] = SDImportData::processWikiText( $val, $pageTitle );
					}
				}
				
				// Empty array
				$table = array();
				// We not assume preprocessing here
				$checkstr = trim( $input );
				if ( !empty( $checkstr ) ) {
					$table = self::getCSVData( $input, $separator, $delimiter );
				}

				// wfErrorLog( "SELF: ".print_r($table), '/tmp/my-custom-debug.log' );


				foreach ( $table as $row ) {
					$fieldcount = 0;
					$struct = array();
					foreach ( $row as $field ) {

						$field = trim( $field );
						
						if ( ! empty( $field ) ) {
							$pretxt = "";
							if ( isset( $pre[ $fieldcount ] ) && !empty( $pre[ $fieldcount ] ) ) {
								$pretxt = $pre[ $fieldcount ].":"; // : for pre
							}
							$postxt = "";
							if ( isset( $post[ $fieldcount ] ) && !empty( $post[ $fieldcount ] ) ) {
								$postxt = "@".$post[ $fieldcount ]; // @ for post
							}
							if ( array_key_exists( $fieldcount, $fields ) ) {
								$struct[ $fields[ $fieldcount ] ] =  $pretxt.$field.$postxt;
							}
						}
						$fieldcount++;
					}
					foreach ( $dprops as $dpropk => $dpropv ) {
						$struct[ $dpropk ] = $dpropv;
					}
					
					SDImportData::insertInternalObject( $parser, $pageTitle, $object, $struct );
				}

			}

		}
		
		if ( !empty( $input ) ) {
			$wgOut = $parser->getOutput();

			global $wgScriptPath;
			$handsonpath = $wgScriptPath."/extensions/SemanticDataImport/libs/handsontable/handsontable.full.js";
			$wgOut->addHeadItem( '<script src="'.$handsonpath.'"></script>' ); //Hack because of handsontable for last versions :/
			$wgOut->addModules( 'ext.sdimport' );

			$fieldList = "";
			if ( sizeof( $fields ) > 0 ) {
				$fieldList = " data-cols='".implode(",", $fields)."' ";
			}
			
			$dataedit = "";
			if ( $editable ) {
				$dataedit = "data-edit='data-edit'";
			}

			$output = "<div class='smwdata' data-delimiter='".$delimiter."' data-separator=\"".$separator."\"".$fieldList." ".$dataedit.">".$input."</div>";
		}

		return array( $output, 'noparse' => true, 'isHTML' => true );
	}



	/**
	* @param $input string
	* @param $args array
	* @param $parser Parser
	* @param $frame Frame
	* @return string
	*/
	public static function prepareLink( $input, $args, $parser, $frame ) {

		global $wgSDImportDataPage;

		$wgOut = $parser->getOutput();
		$wgOut->addModules( 'ext.sdimport' );
		
		$model = "json"; // Let's use by default JSON model
		
		// TODO: Define HTML code to trigger
		$output = "<!-- CODE TO BE DEFINED -->";
		
		
		return array( $output, 'noparse' => true, 'isHTML' => true );
		
	}

	private static function getCSVData( $text, $separator="\t", $delimiter='"' ) {

		$table = array();

		if ( empty( $text ) ) {
			return $table;
		}

		$linesCSV = explode( "\n", $text );

		foreach ( $linesCSV as $lineCSV ) {
			if ( !empty( $lineCSV ) ) {
				array_push( $table, str_getcsv( $lineCSV, $separator, $delimiter ) );
			}
		}

		return $table;

	}



}
