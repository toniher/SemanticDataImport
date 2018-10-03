<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
}

/** In this class we store things related to data processing **/

class SDImportData {


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

				$ns = $pageTitle->getNamespace();

				if ( key_exists( $ns, $wgSDImportDataPage ) ) {

					$nsRepo = $wgSDImportDataPage[$ns];

					if ( array_key_exists( "json", $nsRepo ) ) {		

						if ( $nsRepo["json"] ) {

							list( $args, $table ) = self::getJSONContent( $content );
						
							$object = self::getSelector( $args, $nsRepo, "rowobject" ); // String
							$fields = self::getSelector( $args, $nsRepo, "rowfields", "Array" );
							$types = self::getSelector( $args, $nsRepo, "typefields", "Array" ); // Array
							$refs = self::getSelector( $args, $nsRepo, "ref" ); // Hash
							$pre = self::getSelector( $args, $nsRepo, "prefields" ); // Array
							$post = self::getSelector( $args, $nsRepo, "postfields" ); // Array
							$single = self::getSelector( $args, $nsRepo, "single" ); // Boolean

							// Adding properties, unless they exist
							$propertyTypes = self::addPropertyTypes( $fields, $types );
							// TODO: Handling failing, etc.
							self::importProperties( $propertyTypes );
							
							// No more properties added than their types
							if ( sizeof( array_keys( $fields ) ) > sizeof( $propertyTypes ) ) {
								
								for ( $f = sizeof( array_keys( $fields ) ); $f >= sizeof( $propertyTypes ); $f-- ) {
									array_pop( $fields );
								}
								
							}
		
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
										
										if ( $single ) {
											self::insertObjectviaJSON( $wikiPage, $revision, $user, $struct );
										} else {
											self::insertInternalObjectviaJSON( $wikiPage, $revision, $user, $object, $struct );
										}
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
	 * Function for combining properties and types
	 * @param $props array
	 * @param $types array
	 * 
	 * @return array
	*/
	public static function addPropertyTypes( $props, $types ) {
		
		$count = 0;
		
		foreach ( $types as $type ) {
			
			if ( array_key_exists( $count, $props ) ) {
				
				$prop = $props[ $count ];
				$propertyTypes[ $prop ] = $type;
			}
			
			$count++;
			
		}
		
		return $propertyTypes;
	}
	
	/**
	 * Function for importing Properties straight into the wiki
	 * @param $propertyTypes array
	 * @param $overwrite boolean
	 * @param $user User
	 * 
	 * @return array
	*/
	public static function importProperties( $propertyTypes, $overwrite=false, $user=null) {
		
		$edit_summary = "Adding property via SDImport";
		$listProps = array();
		
		foreach ( $propertyTypes as $prop => $type ) {
			
			// Consider going ahead it type is not null
			if ( $type ) {

				// TODO: to consider not hardcoding NS
				$propPageName = "Property:".$prop;
				
				$propTitle = Title::newFromText( $propPageName );
				
				$wikiPage = new WikiPage( $propTitle );
				
				if ( ! $wikiPage->exists() || ( $wikiPage->exists() && $overwrite ) ) {
					
					$text = "[[Has type::".$type."]]";
					
					$new_content = new WikitextContent( $text );
					$status = $wikiPage->doEditContent( $new_content, $edit_summary );
					
					// Adding list
					// TODO: ideally handling Status 
					array_push( $listProps, $prop );
				}
			
			}
			
		}
		
		
		return $listProps;
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
	 * @param struct object
	 * 
	 * @return boolean
     * 
     * Code adapted from: https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2974
	*/
	public static function insertObjectviaJSON( $wikiPage, $revision, $user, $struct ) {
	
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

		$subject = new \SMW\DIWikiPage( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki() );
		
		// TODO: To finish
		foreach ( $struct as $property => $value ) {
			// Struct to iterate
			
			$dataValue = \SMW\DataValueFactory::getInstance()->newDataValueByText( $property, $value, false, $subject );

			$parserData->getSemanticData()->addDataValue( $dataValue );

		}
	
		// This part is used to add the subobject the the main subject
		$parserData->pushSemanticDataToParserOutput();
		$parserData->updateStore();
		
		// Below it works event with maintenance function
		$store = \SMW\StoreFactory::getStore();
		$store->updateData( $parserData->getSemanticData() );
		
		return true;
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
		$parserData->updateStore();

		// Below it works event with maintenance function
		$store = \SMW\StoreFactory::getStore();
		$store->updateData( $parserData->getSemanticData() );
		
		return true;
	}


	/**
	* @first -> First hash
	* @second -> Second hash
	* @key -> Actual key

	* @return variable (depending on case)
	*/
	public static function getSelector( $first, $second, $key, $opt=null ) {
		
		if ( key_exists( $key, $first ) ) {
			// Here process
			
			$array = array();

			if ( is_array( $first[ $key ] ) ) {
				$keyvals = $first[ $key ];
			} else {
				$keyvals = explode( ",", $first[ $key ] );
			}
			
			if ( self::isAssocArray( $keyvals ) || ( $opt && $opt === "Array" ) ) {
				
				return $keyvals;
			
			} else {
			
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
	 * Whether associative array or not
	 * $arr Array
	 * @return boolean
	*/
	
	public static function isAssocArray( array $arr ) {
	    if (array() === $arr) return false;
	    return array_keys($arr) !== range(0, count($arr) - 1);
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
				
				$args = $meta;
				
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
		
		# Default cases
		$newpage = false;
		$contentModel = "wikitext";
		
		$extraInfo = "";

		$ns = $pageTitle->getNamespace();

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
		
		// TODO: Handle if creation of page
		if ( ! $wikipage->exists() ) {
			$newpage = true;
		}
		
		if ( ! $newpage ) {
		
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
		
		}

		$status = 0;
		$tableText = "";
		
		# Allow only in wikitext context
		if ( $contentModel === "wikitext" ) {
		
		
			if ( ! $newpage ) {
				
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
				}
			
			} else {
				
				$tableText = "<smwdata>".$text."</smwdata>";
				
			}
	
	
			if ( ! empty( $tableText ) ) {
				
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
	
	

	/**
	* @text Bulk data text
	* @pagetitle Title of the page
	* @return status of update
	*/

	public static function importJSONBatch( $text, $namespace="", $overwrite=false) {

		$jsonObj = json_decode( $text, true );
		$dataObj = $jsonObj["data"];
		$dataHash = array();
		
		// If namespace is numeric, check if in config
		if ( is_numeric( $namespace ) ) {
			
			global $wgContLang;
			
			if ( $namespace == 0 ) {
				
				$namespace = "";
				
			} else {
			
				if ( $wgContLang->getNsText( intval( $namespace, 10 ) ) ) {
					
					$namespace = $wgContLang->getNsText( intval( $namespace, 10 ) );
				}
			
			}
		}
		
		for ( $x=0; $x <count($dataObj); $x++ ) {

			if ( count( $dataObj[$x] ) > 1 ) {
				
				$pageCell = array_shift(  $dataObj[$x] );
				
				if ( $pageCell !== "" ) {
					
					if ( $namespace === "" ) {
						$pagetitle =  $pageCell;
					} else {
						$pagetitle = $namespace . ':' . $pageCell;
					}	
					
				}
				
				if ( ! $dataHash[ $pagetitle ] ) {
					$dataHash[ $pagetitle ] = array();
				}
				
				array_push( $dataHash[ $pagetitle ], $dataObj[$x] );
				
			}
			
		}
		
		foreach ( $dataHash as $pagetitle => $dataArray ) {
			
			$jsonSubObj = array();
			$jsonSubObj["data"] = $dataArray;
			$jsonSubObj["meta"] = $jsonObj["meta"];

			self::importJSON( json_encode( $jsonSubObj ), $pagetitle, $overwrite );
			
		}
		
		return true;

	}

	/** Import of JSON into a page, let's say, at commit **/
	/**
	* @text Bulk data text
	* @pagetitle Title of the page
	* @return status of update
	*/
	public static function importJSON( $text, $pagetitle, $overwrite=false) {

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
						$content = new JSONContent($text);
						$status = $wikipage->doEditContent( $content, "Updating content" );
					}
				}
			}
		return $status;
	}
	
	/** TODO: TO BE UPDATED **/
	public static function prepareStructForJSON( $meta, $data ) {
		
		$strJSON = "";
		
		if ( $data ) {
			
			$obj = array( );
			
			// TODO: this may change in future versions
			$obj["meta"] = array();
			$obj["meta"]["app"] = "SDI";
			$obj["meta"]["version"] = 0.1;
			
			if ( $meta ) {
				
				if ( array_key_exists( "rowfields", $meta ) ) {
					$obj["meta"]["rowfields"] = $meta["rowfields"];
				}
			}
			
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
		
		global $wgSDImportDataPage;
		
		// Get Namespace
		
		$context = RequestContext::getMain();
		if ( $context ) {
			$pageTitle = $context->getTitle();
			if ( $pageTitle ) {
				
				$ns = $pageTitle->getNamespace();
				
				if ( array_key_exists( $ns, $wgSDImportDataPage ) ) {
					
					if ( array_key_exists( "form", $wgSDImportDataPage[$ns] )  ) {
						
						if ( $wgSDImportDataPage[$ns]["form"] === true ) {
							
							// Adding form libraries only if needed
							$out->addModules( 'ext.sdimport.form' );
							
						}
					}
				}
				
			}
		}
		
		return $out;
	}
	
	/** This allow PHP vars to be exposed to JavaScript **/
	
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		
		global $wgSDImportDataPage;


		$vars['wgSDImportDataPage'] = $wgSDImportDataPage;

		return true;
	}
}
