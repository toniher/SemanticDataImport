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

			$ns = $pageTitle->getNamespace();

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

			// TODO: Revisit if this still needs handled this way
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
	public static function prepareLink( $parser, $frame, $args ) {

		global $wgSDImportDataPage;

		$wgOut = $parser->getOutput();
		$wgOut->addModules( 'ext.sdimport' );
		
		$attrs_allowed = array( "title", "model", "readonly", "ref", "readOnlyfields" );
		
		$attrs = array();
		$output = "";
		$model = "json"; // Let's use by default JSON model
		$pagetitle = null; // No page default. Do nothing
		$ref = null; // No ref hash by default
		$readOnlyfields = null; // No readonlyfields by default
		
		foreach ( $args as $arg ) {
			$arg_clean = trim( $frame->expand( $arg ) );
			$arg_proc = explode( "=", $arg_clean, 2 );
			
			if ( count( $arg_proc ) == 1 ){
				$pagetitle = trim( $arg_proc[0] );
			} else {
			
				if ( in_array( trim( $arg_proc[0] ), $attrs_allowed ) ) {
					$attrs[ trim( $arg_proc[0] ) ] = trim( $arg_proc[1] );
				}
			}
		}
		
		// TODO: Parse more parameters from function
		if ( array_key_exists( "title", $attrs ) ) {
			$pagetitle = $attrs['title'];
		}
		if ( array_key_exists( "model", $attrs ) ) {
			$model = $attrs['model'];
		}
		if ( array_key_exists( "ref", $attrs ) ) {
			$ref = str_replace( "[", "{", $attrs['ref'] );
			$ref = str_replace( "]", "}", $ref );
		}

		if ( array_key_exists( "readOnlyfields", $attrs ) ) {
			$readOnlyfields = str_replace( "[", "{", $attrs['readOnlyfields'] );
			$readOnlyfields = str_replace( "]", "}", $readOnlyfields );
		}
		
		if ( $pagetitle ) {
			
			$dataAttrsStr = "";
			
			if ( $pagetitle ) {
				$dataAttrsStr.= "data-title='$pagetitle'";
			}
			
			if ( $ref ) {
				$dataAttrsStr.= " data-ref='$ref'";
			}
			
			if ( $readOnlyfields ) {
				$dataAttrsStr.= " data-readOnlyfields='$readOnlyfields'";
			}
			
			$dataAttrsStr.= " data-model='$model'";

			if ( array_key_exists( "readonly", $attrs ) ) {
				$dataAttrsStr.= " data-readonly='true'";
			}
			
			$output = "<div class='smwdata-link' ".$dataAttrsStr."></div>";
		
		}
		
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
