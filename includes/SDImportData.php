<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
}

/** In this class we store things related to data processing **/

class SDImportData {

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

		$separator='\t';
		$delimiter='"';
		$fields = array();

		if ( is_object( $pageTitle ) ) {

			$ns = $pageTitle->getNsText();

			if ( key_exists( $ns, $wgSDImportDataPage ) ) {

				$nsRepo = $wgSDImportDataPage[$ns];
				
				$separator = self::getSelector( $args, $nsRepo, "separator" ); // String
				$delimiter = self::getSelector( $args, $nsRepo, "delimiter" ); // String
				$object = self::getSelector( $args, $nsRepo, "rowobject" ); // String
				$fields = self::getSelector( $args, $nsRepo, "rowfields" ); // Array
				$props = self::getSelector( $args, $nsRepo, "typefields" ); // Array
				$refs = self::getSelector( $args, $nsRepo, "ref" ); // Hash
				$pre = self::getSelector( $args, $nsRepo, "prefields" ); // Array
				$post = self::getSelector( $args, $nsRepo, "postfields" ); // Array
				$editable = self::getSelector( $args, $nsRepo, "edit" ); // Boolean
				

				// TODO: Should we add props here if they don't exist?
				

				$dprops = array();

				if ( $refs ) {
					foreach ( $refs as $key => $val ) {
						$dprops[ $key ] = self::processWikiText( $val, $pageTitle );
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
							$struct[ $fields[ $fieldcount ] ] =  $pretxt.$field.$postxt;
						}
						$fieldcount++;
					}
					foreach ( $dprops as $dpropk => $dpropv ) {
						$struct[ $dpropk ] = $dpropv;
					}
					
					self::insertInternalObject( $parser, $object, $struct );
				}

			}

		}
		
		if ( !empty( $input ) ) {
			$wgOut = $parser->getOutput();
			$wgOut->addModules( 'ext.sdimport' );

			$fieldList = "";
			if ( sizeof( $fields ) > 0 ) {
				$fieldList = " data-cols='".implode(",", $fields)."' ";
			}
			
			$dataedit = "";
			if ( $editable ) {
				$dataedit = "data-edit='data-edit'";
			}

			$output = "<div class='smwdata' data-delimiter='".$delimiter."' data-separator='".$separator."'".$fieldList." ".$dataedit.">".$input."</div>";
		}

		return array( $output, 'noparse' => true, 'isHTML' => true );
	}

	/**
	 * @param $pageTitle Title
	 * @param $object string
	 * @param struct object
	 * 
	 * @return boolean
	*/
	public static function insertInternalObject( $parser, $object, $struct ) {

		$pageTitle = $parser->getTitle();

		$subobjectArgs = array( &$parser );
		// Blank first argument, so that subobject ID will be
		// an automatically-generated random number.
		$subobjectArgs[1] = '';
		// "main" property, pointing back to the page.
		$mainPageName = $pageTitle->getText();
		$mainPageNamespace = $pageTitle->getNsText();
		if ( $mainPageNamespace != '' ) {
			$mainPageName = $mainPageNamespace . ':' . $mainPageName;
		}
		$subobjectArgs[2] = $object . '=' . $mainPageName;

		foreach ( $struct as $prop => $value ) {
			$subobjectArgs[] = $prop . '=' . $value;
		}

		if ( class_exists( 'SMW\SubobjectParserFunction' ) ) {
			// SMW 1.9+
			$subobjectFunction = \SMW\ParserFunctionFactory::newFromParser( $parser )->getSubobjectParser();
			return $subobjectFunction->parse( new SMW\ParserParameterFormatter( $subobjectArgs ) );
		} else {
			// SMW 1.8
			call_user_func_array( array( 'SMWSubobject', 'render' ), $subobjectArgs );
		}
		return;
	}


	/**
	* @first -> First hash
	* @second -> Second hash
	* @key -> Actual key

	* @return variable (depending on case)
	*/
	private static function getSelector( $first, $second, $key ) {

		if ( key_exists( $key, $first ) ) {
			// Here process
			$array = array();

			$keyvals = explode( ",", $first[ $key ] );

			if ( count( $keyvals ) < 2 ) {
				// If => ergo hash
				$keyhvals = explode( "#", $keyvals[0], 2 );

				if ( count( $keyhvals ) > 1 ) {
					$array[ trim( $keyhvals[0] ) ] = trim( $keyhvals[1] );
					return $array;
				} else {
					return trim( $keyhvals[0] );
				}
			} else { 

				foreach ( $keyvals as $keyval ) {
					$keyval = trim( $keyval );
					
					// If => ergo hash
					$keyhvals = explode( "#", $keyval, 2 );
	
					if ( count( $keyhvals ) > 1 ) {
						$array[ trim( $keyhvals[0] ) ] = trim( $keyhvals[1] );
					}
					else {
						array_push( $array, trim( $keyhvals[0] ) );
					}
				}

				return $array;
			}

		} else {
			if ( key_exists( $key, $second ) ) {
				// Direct
				return $second[$key];
			} else {
				return false;
			}
		}
		
	}


	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @param text string
	 *
	 * @return boolean
	*/
	public static function processWikiText( $text, $pageTitle ) {

		// TODO: Ideally a full wikitext processing here, etc.

		if ( $text == '{{PAGENAME}}' ) {
			$text = $pageTitle->getText();
		} elseif ( $text == '{{FULLPAGENAME}}' ) {
			$text = $pageTitle->getPrefixedText();
		} else {
			// Do nothing;
		}

		return $text;
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


	/** Import of Configuration, let's say, at commit */
	/**
	* @text Bulk data text
	* @pagetitle Title of the page
	* @delimiter Delimiter of CSV
	* @enclosure Enclosure of CSV
	* @return status of update
	*/
	public static function importConf( $text, $pagetitle, $separator=',', $delimiter="\"" ) {

		$title = Title::newFromText( $pagetitle );
		$wikipage = WikiPage::factory( $title );
		
		// TODO: Only append extra attrs if different from default conf

		$prefix = "<smwdata data-separator='".$separator."' data-delimiter='".$delimiter."'>";
		$sufix = "</smwdata>";
		$text = $prefix."\n".$text."\n".$sufix."\n";
		
		$content = new WikiTextContent( $text );
		$status = $wikipage->doEditContent( $content, "Updating content" );

		return $status;
		
	}

}
