/*global $ document jQuery console mw window wgScriptPath alert location */

var tableSDImport = {};

// TODO: Handle delimiter

/** Load SpreadSheet **/
(function($, mw) {

	$(document).ready( function() {

		
		var readonly = true;
		var extrarows = 0;
		var numdata = 0;
		
		var SDIJSONpage = false;
		
		// Detect namespace
		// If JSON namespace
		
		// TODO: Handle main page namespace, alias, etc.
		var pageTitle = mw.config.get( "wgCanonicalNamespace" ) + ":" + mw.config.get("wgTitle");

		var detectTableNS = mw.config.get( "wgCanonicalNamespace" );
		
		var pageConfig = mw.config.get( "wgSDImportDataPage" );
		
		if ( pageConfig ) {
			
			if ( pageConfig.hasOwnProperty( detectTableNS ) ) {
				
				var actualNS = pageConfig[ detectTableNS ];
				
				if ( actualNS.hasOwnProperty( "json" ) ) {
					
					SDIJSONpage = actualNS.json;
				}
				
				if ( actualNS.hasOwnProperty( "edit" ) ) {
					
					readonly = ! actualNS.edit; // Opposite of edit
				}
			}
			
		}

		if ( SDIJSONpage ) {
			
			// Get API
			// /w/api.php?action=query&prop=revisions&rvprop=content&format=jsonfm&formatversion=2&titles=SDImport:Test
			
			var param = {};
			param.action = "query";
			param.prop = "revisions";
			param.rvprop = "content";
			param.format= "json";
			param.formatversion = 2;
			param.titles = pageTitle;
			
			var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
			posting.done(function( data ) {

				// Create Handsontable from content
				var celldata = createTableFromJSON( data );
			
				if ( celldata && celldata.hasOwnProperty( "data" ) ) {
				
					var divval = "SMWData-"+numdata;
					
					// Temp TODO: Columns handling
					var cols = null;
					if ( celldata.hasOwnProperty("meta") ) {
						if ( celldata.meta.hasOwnProperty("rowfields") ) {
							cols = celldata.meta.rowfields;
						}
					}
					
					// Endpoint where to add - Put as first child
					$("#mw-content-text").prepend("<div id='"+divval+"'>");
				
					// TODO: Handle edit mode
				
					var container  = document.getElementById( divval );
				
					var table = new Handsontable( container, {
						data: celldata.data,
						readOnly: readonly,
						minSpareRows: extrarows,
						colHeaders: cols,
						contextMenu: true,
						columnSorting: true
					});
				
					// Let's store in global variable
					tableSDImport[ divval ] = table;
				
					if ( ! readonly ) {
						$( container ).append("<p class='smwdata-commit-json' data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");
					}
					
					numdata = numdata + 1 ;
				
				}
			
			})
			.fail( function( data ) {
				alert("Error!");
			});
				
			
		} else {
			
			// Handle smwdata-link
			$('.smwdata-link').each( function() {
	
				var divval = "SMWData-"+numdata;
				$(this).after("<div id='"+divval+"'></div>");
				
				var container  = document.getElementById( divval );
				
				// Let's make readOnly false
				readonly = false;

				var pagetitle = null;
				var model = "json";
				
				// Let's check if content in title
				if ( $(this).data('title') ) {
					pagetitle = $(this).data('title');
				}
		
				if ( $(this).data('model') ) {
					model = $(this).data('model');
				}
				
				if ( $(this).data('readonly') ) {
					readonly = true;
				}			

				// TODO: Refactor with SDIJSONpage part
				if ( pagetitle ) {
			
					var param = {};
					param.action = "query";
					param.prop = "revisions";
					param.rvprop = "content";
					param.format= "json";
					param.formatversion = 2;
					param.titles = pagetitle;


					var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
					posting.done(function( data ) {

					var celldata = createTableFromJSON( data );
					
					if ( celldata && celldata.hasOwnProperty( "data" ) ) {
									
						cols = getDefaultCols( pagetitle );
						if ( celldata.hasOwnProperty("meta") ) {
							if ( celldata.meta.hasOwnProperty("rowfields") ) {
								cols = celldata.meta.rowfields;
							}
						}
				
						// Create Handsontable from content
						var table = new Handsontable( container, {
							data: celldata.data,
							readOnly: readonly,
							minSpareRows: extrarows,
							colHeaders: cols,
							contextMenu: true,
							columnSorting: true
						});
		
						// Let's store in global variable
						tableSDImport[ divval ] = table;
					} else {
						
						cols = getDefaultCols( pagetitle );
						celldata = [ [ "", "", "" ] ];
						extrarows = 3;
						
						fillEmptyTable( container, divval, celldata, cols, extrarows, readonly );
					}
					})
					.fail( function( data ) {
						alert("Error!");
					});
				
				} else {

					cols = getDefaultCols( pagetitle );
					celldata = [ [ "", "", "" ] ];
					extrarows = 3;
				
					fillEmptyTable( container, divval, celldata, cols, extrarows, readonly );
					
				}

				
				if ( model === "json" ) {
					
					var pagetitleStr = "";
					if ( pagetitle ) {
						pagetitleStr = "data-title='"+pagetitle+"'";
					}
					$( container ).append("<p class='smwdata-commit-json' " + pagetitleStr + " data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");

				}
				
				numdata = numdata + 1 ;
				
			});
			
			
			// Handle smwdata function
			$('.smwdata').each( function() {
		
				var celldata = [];
		
				var separator = "\t";
				var delimiter = '"';
		
				if ( $(this).data('separator') ) {
					separator = $(this).data('separator');
				}
		
				if ( $(this).data('delimiter') ) {
					delimiter = $(this).data('delimiter');
				}
		
				var text = $(this).text();
				var lines = text.split("\n");
			
				for ( var i = 0; i< lines.length; i++ ) {
		
					if ( lines[i] !== "" ) {
						var row = lines[i].split(separator);
						celldata.push( row );
					}
		
				}
		
				var strcols = $(this).attr("data-cols");
				var cols = strcols.split(",");
		
				var divval = "SMWData-"+numdata;
				$(this).after("<div id='"+divval+"'></div>");
		
				$(this).hide();
	
				if ( $(this).data('edit') ) {
					readonly = false;
				}
	
				var container  = document.getElementById( divval );
		
				var table = new Handsontable( container, {
					data: celldata,
					readOnly: readonly,
					minSpareRows: extrarows,
					colHeaders: cols,
					contextMenu: true,
					columnSorting: true
				});
		
				// Let's store in global variable
				tableSDImport[ divval ] = table;
		
				if ( $(this).data('edit') ) {
					$( container ).append("<p class='smwdata-commit' data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");
				}
				
				numdata = numdata + 1 ;
	
			});
		
		}

	});
	
	
	$( document ).on( "click", ".smwdata-commit", function() {
	
		var param = {};
		var selector = $(this).attr('data-selector');

		param.separator="\t";
		param.delimiter='"';
		
		var parent = $(this).parent().parent().find('.smwdata').get(0);
	
		if ( $(parent).data('separator') ) {
			param.separator = $(parent).data('separator');
		}
	
		if ( $(parent).data('delimiter') ) {
			param.delimiter = $(parent).data('delimiter');
		}

		param.num = parseInt( selector.replace( "#SMWData-", "" ), 10 );
	
		param.title = mw.config.get( "wgCanonicalNamespace" ) + ":" + mw.config.get("wgTitle");
	
		param.action = "sdimport";
		param.format = "json";


		var instance = tableSDImport[ selector ];
		var rows = instance.countRows();

		var data = [];

		// Push
		for ( var r = 0; r < rows; r = r + 1 ) {
			data.push( instance.getDataAtRow( r ) );
		}

		//Let's get data from selector
		param.text = convertData2str( data, param.separator, param.delimiter );

		var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
		posting.done(function( data ) {
			var newlocation = location.protocol + '//' + location.host + location.pathname;
			// Go to page with no reloading (with no reload)
			window.setTimeout( window.location.href = newlocation, 1500);
		})
		.fail( function( data ) {
			alert("Error!");
		});
	});
	
	$( document ).on( "click", ".smwdata-commit-json", function() {

		var param = {};
		var selector = $(this).attr('data-selector');
		
		var pagetitle = $(this).attr('data-title');


		var instance = tableSDImport[ selector ];

		var rows = instance.countRows();

		var data = [];

		// Push
		for ( var r = 0; r < rows; r = r + 1 ) {
			data.push( instance.getDataAtRow( r ) );
		}
		
		var cols = instance.getColHeader();
		
		// TODO: Handle at least rowobject and single mode as well
		var meta = null;
		
		if ( cols ) {
			
			if ( cols.length > 0 && ! cols.every( el => el === null ) ) {

				meta = {};
				meta.rowfields = cols;
			}
		}
		
		var strJSON = prepareStructForJSON( meta, data );
		
		if ( strJSON ) {
		
			if ( pagetitle ) {
				param.title = pagetitle;
			} else {
				param.title = mw.config.get( "wgCanonicalNamespace" ) + ":" + mw.config.get("wgTitle");
			}
		
			param.action = "sdimport";
			param.format = "json";
			param.model = "json";
			param.overwrite = true; // By default, let's overwrite content
		
			param.text = strJSON;
			
			var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
			posting.done(function( data ) {		
				var newlocation = location.protocol + '//' + location.host + location.pathname;
				// Go to page with no reloading (with no reload)
				window.setTimeout( window.location.href = newlocation, 1500);
			})
			.fail( function( data ) {
				alert("Error!");
			});
				
		}
		
	
	});
	
	/** @param Array
	* return string
	**/
	function convertData2str ( data, separator, delimiter ) {
		var str = "";
		var newArr = [];
		if ( data.length > 0 ) {
			// We put \\n or \\t for ensuring proper conversion afterwards
			for ( var i = 0; i < data.length; i++ ) {
				// TODO: Handle enclosure
				var rowstr = data[i].join(separator);
				newArr.push( rowstr );
			}
			str = newArr.join("\n");
		}
	
		return str;
	}
	
	
	function createTableFromJSON( data ) {
		
		var tableData = null;
		
		if ( data ) {
			
			if ( data.hasOwnProperty( "query" ) ) {
				
				if ( data["query"].hasOwnProperty( "pages" ) ) {
				
					var pages = data["query"]["pages"];
					
					if ( pages.length > 0 ) {
						
						var page = pages[0]; // Let's get only first page
						
						if ( page && page.hasOwnProperty( "revisions" ) ) {
							
							var revisions = page.revisions;
							
							if ( revisions.length > 0 ) {
								
								var revision = revisions[0]; // Let's get only first rev
								
								if ( revision && revision.hasOwnProperty("content") && revision.hasOwnProperty("contentmodel") ) {
									
									var contentmodel = revision.contentmodel;
									
									if ( contentmodel === "json" ) { // Let's only take JSON contentmodel
										
										var content = revision.content;
										
										// Check if proper JSON
										if ( isJSONString( content ) ) {
											
											var JSONcontent = JSON.parse( content );
											
											// Let's check if SDI JSON
											tableData = processJSONcontent( JSONcontent );
											
											
										}
									}
									
								}
							}
							
						}
						
					}
					
				}
		
			}
			
		}
		
		return tableData;
		
	}
	
	function isJSONString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	
	function processJSONcontent( JSONcontent ) {
		
		var tableData = {};
		
		if ( JSONcontent.hasOwnProperty( "meta" ) ) {
			
			if ( JSONcontent["meta"].hasOwnProperty( "app" ) ) {
				
				if ( JSONcontent["meta"]["app"] === "SDI" ) {
					
					if ( JSONcontent.hasOwnProperty( "data" ) ) {
						
						if ( Array.isArray( JSONcontent["data"] ) ) {
							
							tableData.data = JSONcontent["data"];
						}
					}
					
					tableData.meta = JSONcontent["meta"];
					
				}
				
			}
	
		}
											
		return tableData;
		
	}
	
	function prepareStructForJSON( meta, data ) {
		
		var strJSON = null;
		
		if ( data ) {
			
			var obj = {};
			
			// TODO: this may change in future versions
			obj.meta = {};
			obj.meta.app = "SDI";
			obj.meta.version = 0.1;

			if ( meta ) {
				
				if ( meta.hasOwnProperty( "rowfields" ) ) {
					obj.meta.rowfields = meta.rowfields;
				}
			}
			
			obj.data = data;
			
			strJSON = JSON.stringify( obj );
		}
		
		
		return strJSON;
	}
	
	
	function fillEmptyTable( container, divval, celldata, cols, extrarows, readonly ) {
		
		var table = new Handsontable( container, {
			data: celldata,
			readOnly: readonly,
			minSpareRows: extrarows,
			colHeaders: cols,
			contextMenu: true,
			columnSorting: true
		});

		// Let's store in global variable
		tableSDImport[ divval ] = table;
	}

	/** Get default columns from pageTitle **/	
	function getDefaultCols( pageTitle ) {
		
		var cols = null;
		
		if ( pageTitle ) {
			
			var parts = pageTitle.split(":");
			
			var detectTableNS = null;
			
			if ( parts.length > 1 ) {
				
				detectTableNS = parts[0];
			}
			
			if ( detectTableNS ) {
		
				var pageConfig = mw.config.get( "wgSDImportDataPage" );
				
				if ( pageConfig ) {
					
					if ( pageConfig.hasOwnProperty( detectTableNS ) ) {
						
						var actualNS = pageConfig[ detectTableNS ];
						
						if ( actualNS.hasOwnProperty( "rowfields" ) ) {
							
							cols = actualNS.rowfields;
						}
						
					}
					
				}
			
			}

		}
		
		return cols;
		
		
	}
	

}( jQuery, mediaWiki ) );
