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

		$separator="\t";
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
							if ( array_key_exists( $fieldcount, $fields ) ) {
								$struct[ $fields[ $fieldcount ] ] =  $pretxt.$field.$postxt;
							}
						}
						$fieldcount++;
					}
					foreach ( $dprops as $dpropk => $dpropv ) {
						$struct[ $dpropk ] = $dpropv;
					}
					
					self::insertInternalObject( $parser, $pageTitle, $object, $struct );
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
	 * Occurs after the save page request has been processed.
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param Content $content
	 * @param string $summary
	 * @param boolean $isMinor
	 * @param boolean $isWatch
	 * @param $section Deprecated
	 * @param integer $flags
	 * @param {Revision|null} $revision
	 * @param Status $status
	 * @param integer $baseRevId
	 * @param integer $undidRevId
	 *
	 * @return boolean
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	public static function saveJSONData( $wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId, $undidRevId=null ) {
		
		global $wgSDImportDataPage;

		if ( $wikiPage ) {
			
			// Get NS
			$pageTitle = $wikiPage->getTitle();

			if ( is_object( $pageTitle ) ) {

				$ns = $pageTitle->getNsText();

				if ( key_exists( $ns, $wgSDImportDataPage ) ) {

					$nsRepo = $wgSDImportDataPage[$ns];

					if ( array_key_exists( "json", $nsRepo ) ) {		

						if ( $nsRepo["json"] ) {

							list( $args, $table ) = self::getJSONContent( $content );
						
							$object = self::getSelector( $args, $nsRepo, "rowobject" ); // String
							$fields = self::getSelector( $args, $nsRepo, "rowfields" ); // Array
							$props = self::getSelector( $args, $nsRepo, "typefields" ); // Array
							$refs = self::getSelector( $args, $nsRepo, "ref" ); // Hash
							$pre = self::getSelector( $args, $nsRepo, "prefields" ); // Array
							$post = self::getSelector( $args, $nsRepo, "postfields" ); // Array
							
		
							// TODO: Should we add props here if they don't exist?
		
							$dprops = array();
		
							if ( $refs ) {
								foreach ( $refs as $key => $val ) {
									$dprops[ $key ] = self::processWikiText( $val, $pageTitle );
								}
							}
							
							if ( $table ) {
							
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

									if ( count( array_keys( $struct ) ) > 0 ) {
										self::insertInternalObjectviaJSON( $wikiPage, $revision, $user, $object, $struct );
									}
									
								}
							
							}
						
						}
					
					}
		
				}
			
			}
		
		}
		
		return true;
	}
	
	/**
	 * @param $pageTitle Title
	 * @param $object string
	 * @param struct object
	 * 
	 * @return boolean
	*/
	public static function insertInternalObject( $parser, $pageTitle, $object, $struct ) {

		# TODO: Check if this will work
		if ( ! $parser ) {
			$parser = new Parser();
			$parser->setTitle( $pageTitle ); // Put context
		}
		
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
	 * @param $wikiPage wikiPage
     * @param $revision revision
     * @param $user user
	 * @param $object string
	 * @param struct object
	 * 
	 * @return boolean
     * 
     * Code from: https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2974
	*/
	public static function insertInternalObjectviaJSON( $wikiPage, $revision, $user, $object, $struct ) {

		$applicationFactory = \SMW\ApplicationFactory::getInstance();
		
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();
		
		/** * Initialize the ParserOuput object */
		$editInfoProvider = $mwCollaboratorFactory->newEditInfoProvider( $wikiPage, $revision, $user );
		
		$parserOutput = $editInfoProvider->fetchEditInfo()->getOutput();

		if ( !$parserOutput instanceof \ParserOutput ) {
			return true;
		}
		
		$parserData = $applicationFactory->newParserData( $wikiPage->getTitle(), $parserOutput );

		$subject = $parserData->getSubject();
		
		// Identify the content as unique
		$subobjectName = '_SDI' . md5( json_encode( $struct ) );

		$subject = new \SMW\DIWikiPage( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subobjectName );

		// Build the subobject by using a separate container object
		$containerSemanticData = new \SMWContainerSemanticData( $subject );

		// Iterate through here
		
		foreach ( $struct as $property => $value ) {
			// If you don't know the type, use the DataValueFactory
			$dataValue = \SMW\DataValueFactory::getInstance()->newDataValueByText( $property, $value );
			$containerSemanticData->addDataValue( $dataValue );
		}
		
		// Object assignation
		
		if ( $object ) {
			
			if ( ! empty( $object ) ) {
				
				if ( $wikiPage->getTitle() ) {
					
					$fullTitle = $wikiPage->getTitle()->getPrefixedText();
					
					$dataValue = \SMW\DataValueFactory::getInstance()->newDataValueByText( $object, $fullTitle );
					$containerSemanticData->addDataValue( $dataValue );
					
				}
				
			}
			
		}
		
		
		// This part is used to add the subobject the the main subject
		$parserData->getSemanticData()->addPropertyObjectValue( new \SMW\DIProperty( \SMW\DIProperty::TYPE_SUBOBJECT ), new \SMWDIContainer( $containerSemanticData ) );
		$parserData->pushSemanticDataToParserOutput();
		
		
		return true;
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

	
	private static function getJSONContent( $content ) {

		$outcome = array( );
		$args = null;
		$data = null;

		if ( $content ) {
			
			if ( is_object( $content ) ) {
				
				if ( $content->getModel() == CONTENT_MODEL_JSON ) {

					// Only act if JSON
					
					$json = $content->getNativeData();
					
					list( $args, $data ) = self::processJSON( $json );
				}
				
			}
		}
		
		array_push( $outcome, $args );
		array_push( $outcome, $data );

		return $outcome;
	}

	
	private static function processJSON( $json ) {
		
		$outcome = array( );
		$args = null;
		$data = null;
		
		$SDIJSON = false;
		
		// Check JSON is valid
		$jsonObj = json_decode( $json, true );
		
		if ( $jsonObj ) {
		
			if ( array_key_exists( "meta", $jsonObj ) ) {
				
				$meta = $jsonObj["meta"];

				if ( array_key_exists( "app", $meta ) ) {
					
					if ( $meta["app"] === "SDI" ) {
						$SDIJSON = true;
					}
				}
				
				if ( array_key_exists( "version", $meta ) ) {

					$args["version"] = $meta["version"];
					
				}
				
				# TODO: Addding more custom fields to args
				
				
				if ( array_key_exists( "data", $jsonObj ) && $SDIJSON ) {
				
					$dataObj = $jsonObj["data"];
				
					$data = self::checkJSONData( $dataObj );
				
				}
			}
		
		}
		
		array_push( $outcome, $args );
		array_push( $outcome, $data );

		return $outcome;	
		
	}
	
	private static function checkJSONData( $dataObj ) {
		
		$data = null;
		
		if ( is_array( $dataObj ) ) {
			
			if ( count( $dataObj ) > 0 ) {
				
				$bad = false;
				
				// We should have an array of arrays
				foreach ( $dataObj as $row ) {
					
					if ( ! is_array( $row ) ) {
						$bad = true;
					}
				}
				
				if ( ! $bad ) {
					$data = $dataObj;
				}
				
			}
		}
		
		return $data;
		
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


	/** Import of Wikitext, let's say, at commit */
	/**
	* @text Bulk data text
	* @pagetitle Title of the page
	* @delimiter Delimiter of CSV
	* @enclosure Enclosure of CSV
	* @num Occurrence in page. If only one, then 0
	* @return status of update
	*/
	public static function importWikiText( $text, $pagetitle, $separator=NULL, $delimiter=NULL, $num=0 ) {

		$title = Title::newFromText( $pagetitle );
		$wikipage = WikiPage::factory( $title );
		
		$extraInfo = "";

		$ns = $title->getSubjectNsText();
		if ( $GLOBALS["wgSDImportDataPage"] && array_key_exists( $ns, $GLOBALS["wgSDImportDataPage"] ) ) {

			if ( $separator !== NULL ) {
				if ( array_key_exists( "separator", $GLOBALS["wgSDImportDataPage"][$ns] ) ) {
					if ( $GLOBALS["wgSDImportDataPage"][$ns]["separator"] != $separator ) {
						$extraInfo = $extraInfo . " separator=\"".$separator."\"";
					}
				}
			}
			if ( $delimiter !== NULL ) {
				if ( array_key_exists( "delimiter", $GLOBALS["wgSDImportDataPage"][$ns] ) ) {
					if ( $GLOBALS["wgSDImportDataPage"][$ns]["delimiter"] != $delimiter ) {
						$extraInfo = $extraInfo . " delimiter='".$delimiter."' ";
					}
				}
			}
		}

		// Retrievet text of page
		// Back-compatibility, just in case
		if ( method_exists ( $wikipage, "getContent" ) ) {
			$mainContent = $wikipage->getContent();
			$mainText = $mainContent->getNativeData();
			$contentModel = $wikipage->getContentModel();
			
			if ( ! $contentModel ) {
				$contentModel = "wikitext";
			}
			
		} else {
			$mainText = $wikipage->getText();
			$contentModel = "wikitext";
		}

		$status = 0;
		
		# Allow only in wikitext context
		if ( $contentModel === "wikitext" ) {
		
			// Get matches
			$page_parts = preg_split( "/(<smwdata.*?>)/", $mainText, -1, PREG_SPLIT_DELIM_CAPTURE );
	
			$count = 0;
			$outcome = array();
	
			foreach ( $page_parts as $page_part ) {
	
				if ( preg_match( "/<smwdata/", $page_part ) ) {
					$count = $count + 1;
				} else {
					if ( $num == $count - 1 ) {
						if ( preg_match( "/<\/smwdata/", $page_part ) ) {
	
							$in_parts = preg_split( "/(<\/smwdata.*?>)/", $page_part, -1, PREG_SPLIT_DELIM_CAPTURE );
							$in_parts[0] = "\n".$text."\n";
							$page_part = implode( "", $in_parts );
						}
					}
				}
	
				array_push( $outcome, $page_part );
			}
	
	
			// If stuff
			if ( count( $outcome ) > 0 ) {
	
				$tableText = implode( "", $outcome );
				
				// Submit content
				// Back-compatibility, just in case
				if ( method_exists ( $wikipage, "doEditContent" ) ) {
					$content = new WikiTextContent( $tableText );
					$status = $wikipage->doEditContent( $content, "Updating content" );
				} else {
					$status = $wikipage->doEdit( $tableText, "Updating content" );
				}
	
			}
			
			// TODO: Handle status value if not normal one
		
		}

		return $status;
	}
	
	
	/** Import of JSON into a page, let's say, at commit **/
	/**
	* @text Bulk data text
	* @pagetitle Title of the page
	* @return status of update
	*/
	public static function importJSON( $text, $pagetitle, $overwrite=false ) {
		
		$title = Title::newFromText( $pagetitle );
		$wikipage = WikiPage::factory( $title );
		
		$goahead = true;
		
		$status = false;
		
		// Check if exists
		if ( $wikipage->exists() &&  ! $overwrite ) {
			$goahead = false;
		}
		
		if ( $goahead ) {
			
			// Check compatibility. Only if newer versions of MW
			if ( method_exists ( $wikipage, "getContent" ) ) {
	
				$contentModel = $wikipage->getContentModel();
				
				if ( $contentModel === "json" || ! $wikipage->exists() ) {
					
					$content = new JSONContent( $text );

					$status = $wikipage->doEditContent( $content, "Updating content" );
					
					// TODO: Handle status value if not normal one

				}
			}
			
		}
		
		return $status;
		
	}
	
	
	public static function prepareStructForJSON( $data ) {
		
		$strJSON = "";
		
		if ( $data ) {
			
			$obj = array( );
			
			// TODO: this may change in future versions
			$obj["meta"] = array();
			$obj["meta"]["app"] = "SDI";
			$obj["meta"]["version"] = 0.1;
			
			$obj["data"] = $data;
			
			$strJSON = json_encode( $obj );
		}
		
		
		return $strJSON;
	}
	

	/**
	* @param $out OutputPage
	* @param $text string
	* @return $out OutputPage
	*/
	
	public static function onOutputPageBeforeHTML( &$out, &$text ) {

		// We add Modules
		$out->addModules( 'ext.sdimport' );
		
		return $out;
	}
	
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		
		global $wgSDImportDataPage;


		$vars['wgSDImportDataPage'] = $wgSDImportDataPage;

		return true;	
	}

}
