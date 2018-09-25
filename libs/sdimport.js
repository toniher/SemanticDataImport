/*global $ document jQuery console mw window wgScriptPath alert location */

var tableSDImport = {};
var formSDImport = {};

// TODO: Handle delimiter

/** Load SpreadSheet **/
(function($, mw) {

	$(document).ready( function() {

		
		var readonly = true;
		var editfields = false; // Do not allow editing fields by default
		var formmode = false; // Detect if form mode
		var extrarows = 0;
		var numdata = 0;
		var typefields = null;
		var readOnlyfields = null;
		
		var SDIJSONpage = false;
		
		// Detect namespace
		// If JSON namespace
		
		// TODO: Handle alias, etc.
		var detectTableNS = mw.config.get( "wgNamespaceNumber" );
		var pageTitle = mw.config.get("wgPageName");
		
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
				
				if ( actualNS.hasOwnProperty("editfields") ) {
					
					editfields = actualNS.editfields;
				}
				
				if ( actualNS.hasOwnProperty("typefields") ) {
					
					typefields = actualNS.typefields;
				}
				
				if ( actualNS.hasOwnProperty("readOnlyfields") ) {
					
					readOnlyfields = actualNS.readOnlyfields;
				}			
				
				
				if ( actualNS.hasOwnProperty("form") ) {
					
					formmode = actualNS.form;
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
				
				var cols = null;
				var rowobj = null;
				var tcols = [];

				// Create Handsontable from content
				var celldata = createTableFromJSON( data );
			
				if ( celldata && celldata.hasOwnProperty( "data" ) ) {
				
					var divval = "SMWData-"+numdata;
					
					var singleStr = "";
					var refStr = "";
					
					cols = getRowParameter( detectTableNS, "rowfields" );
					rowobj = getRowParameter( detectTableNS, "rowobject" );
					
					if ( celldata.hasOwnProperty("meta") ) {

						if ( celldata.meta.hasOwnProperty("rowfields") ) {
							cols = celldata.meta.rowfields;
						}
						
						if ( celldata.meta.hasOwnProperty("single") ) {
							singleStr = " data-single='" + celldata.meta.single + "'";
						}

						if ( celldata.meta.hasOwnProperty("ref") ) {
							refStr = " data-ref='" + JSON.stringify( celldata.meta.ref ) + "'";
						}
						
						if ( celldata.meta.hasOwnProperty("rowobject") ) {
							rowobj = celldata.meta.rowobject;
						}
						
						if ( celldata.meta.hasOwnProperty("readOnlyfields") ) {
							readOnlyfields = celldata.meta.readOnlyfields;
						}
						
					}
					
					// Handle specific cols readonly permissions. cols structure is changes
					if ( readOnlyfields ) {
						
						if ( cols ) {
							
							for ( var c=0; c < cols.length; c++ ) {
								
								if ( readOnlyfields.includes( cols[c] ) ) {
									
									tcols.push( c );
									
								}

							}
														
						}
						
					}
					
					
					// Endpoint where to add - Put as first child
					$("#mw-content-text").prepend("<div id='"+divval+"'"+singleStr+refStr+">");
				
					// TODO: Handle edit mode

					if ( ! formmode || singleStr === "" ) {
						
						var contextmenu = true;
						
						if ( readonly ) {
							contextmenu = false;
						}
						
						var container  = document.getElementById( divval );
						
						var table = new Handsontable( container, {
							data: celldata.data,
							readOnly: readonly,
							minSpareRows: extrarows,
							colHeaders: cols,
							rowHeaders: rowobj,
							contextMenu: contextmenu,
							columnSorting: true
						});
						
						if ( tcols.length > 0 ) {

							table.updateSettings({
								cells: function (row, col) {
									var cellProperties = {};
							  
									if ( tcols.includes( col ) ) {
										cellProperties.readOnly = true;
									}
							  
									return cellProperties;
								}
							});
						
						}
					
						// Let's store in global variable
						tableSDImport[ divval ] = table;
					
						if ( ! readonly ) {
							$( container ).append("<p class='smwdata-commit-json' data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");
						
							if ( editfields ) {
								changeTableHeader( divval, table );
								// addEditRowInput( rowobj, divval );
							}
						}
					
					} else {
						// Only when form and single modes
						
						var jsonForm = createFormFromData( celldata.data, cols, typefields, readOnlyfields );
						
						formSDImport[divval] = new Survey.Model( jsonForm );
						
						formSDImport[divval]
							.onComplete
							.add(function (result) {

								submitSDIimportJSON( '#'+divval, result.data );
								document
									.querySelector('#'+divval )
									.innerHTML = "";
							});
						
						$("#"+divval).Survey({model: formSDImport[divval]});


					}
					
					numdata = numdata + 1 ;
				
				}
			
			})
			.fail( function( data ) {
				alert("Error!");
			});
				
			
		} 
			
		// Handle smwdata-link
		$('.smwdata-link').each( function() {

			var divval = "SMWData-"+numdata;
			
			var linkcontainer = this;

			$(linkcontainer).after("<div id='"+divval+"'></div>");
			container = document.getElementById( divval );

			// Let's make readOnly false
			readonly = false;

			var pagetitle = null;
			var model = "json";
			var rowobj = null;
			var reflink = null;
			var readOnlyfields = null;
			var tcols = [];

			var table;
			
			// Let's check if content in title
			if ( $(linkcontainer).data('title') ) {
				pagetitle = $(linkcontainer).data('title');
			}
			
			var namespace = getNamespaceFromTitle( pagetitle );
			
			var edit = getRowParameter( namespace, "edit" );
			
			if ( edit === false ) {
				readonly = true;
			}
			
			readOnlyfields = getRowParameter( namespace, "readOnlyfields" );
	
			// Retrive other data stuff
			if ( $(linkcontainer).data('model') ) {
				model = $(linkcontainer).data('model');
			}
							
			if ( $(linkcontainer).data('readonly') ) {
				readonly = true;
			}
			
			if ( $(linkcontainer).data('ref') ) {
				reflink = $(linkcontainer).data('ref');
			}

			if ( $(linkcontainer).data('readOnlyfields') ) {
				readOnlyfields = $(linkcontainer).data('readOnlyfields');
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

				rowobj = getRowParameter( namespace, "rowobject" );

				var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
				posting.done(function( data ) {

					var celldata = createTableFromJSON( data );
					
					if ( celldata && celldata.hasOwnProperty( "data" ) ) {
						
						cols = getRowParameter( namespace, "rowfields" );
						var singleStr = "";
						var refStr = "";

						if ( celldata.hasOwnProperty("meta") ) {
							if ( celldata.meta.hasOwnProperty("rowfields") ) {
								cols = celldata.meta.rowfields;
							}
						
							if ( celldata.meta.hasOwnProperty("single") ) {
								singleStr = " data-single='" + celldata.meta.single + "'";
							}
							
							if ( celldata.meta.hasOwnProperty("ref") ) {
								refStr = " data-ref='" + celldata.meta.ref + "'";
							}
						
							if ( celldata.meta.hasOwnProperty("rowobject") ) {
								rowobj = celldata.meta.rowobject;
							}
							
							if ( celldata.meta.hasOwnProperty("readOnlyfields") ) {
								readOnlyfields = celldata.meta.readOnlyfields;
							}

						}

						if ( singleStr !== "" ) {
							$( container ).attr("data-single", celldata.meta.single );
						}
						
						if ( refStr !== "" ) {
							$( container ).attr("data-ref", JSON.stringify( celldata.meta.ref ) );
						}

						if ( reflink && reflink !== "" ) {
							$( container ).attr("data-ref", JSON.stringify( reflink ) );
						}

						// Handle specific cols readonly permissions. cols structure is changes
						if ( readOnlyfields ) {
							
							if ( cols ) {
								
								for ( var c=0; c < cols.length; c++ ) {
									
									if ( readOnlyfields.includes( cols[c] ) ) {
										
										tcols.push( c );
										
									}
	
								}
															
							}
							
						}

						// Create Handsontable from content
						table = new Handsontable( container, {
							data: celldata.data,
							readOnly: readonly,
							minSpareRows: extrarows,
							colHeaders: cols,
							rowHeaders: rowobj,
							contextMenu: readonly,
							columnSorting: true
						});
						
						if ( tcols.length > 0 ) {

							table.updateSettings({
								cells: function (row, col) {
									var cellProperties = {};
							  
									if ( tcols.includes( col ) ) {
										cellProperties.readOnly = true;
									}
							  
									return cellProperties;
								}
							});
						
						}
		
						// Let's store in global variable
						tableSDImport[ divval ] = table;

					} else {
						
						cols = getRowParameter( namespace, "rowfields" );
						celldata = [ [ "", "", "" ] ];
						extrarows = 3;

						table = fillEmptyTable( container, divval, celldata, cols, extrarows, readonly );
					}
					
					if ( model === "json" ) {

						var pagetitleStr = "";
						if ( pagetitle ) {
							pagetitleStr = "data-title='"+pagetitle+"'";
						}

						$( container ).append("<p class='smwdata-commit-json' " + pagetitleStr + " data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");
	
						editfields = getRowParameter( namespace, "editfields" );
						
						if ( editfields ) {

							changeTableHeader( divval, table );
							// addEditRowInput( rowobj, divval );
						}
		
					}		
					
				})
				.fail( function( data ) {
					alert("Error!");
				});
			
			} else {

				cols = getRowParameter( namespace, "rowfields" );
				celldata = [ [ "", "", "" ] ];
				extrarows = 3;
			
				table = fillEmptyTable( container, divval, celldata, cols, extrarows, readonly );
			
				if ( model === "json" ) {

					var pagetitleStr = "";
					if ( pagetitle ) {
						pagetitleStr = "data-title='"+pagetitle+"'";
					}
					$( container ).append("<p class='smwdata-commit-json' " + pagetitleStr + " data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");

					editfields = getRowParameter( namespace, "editfields" );
					
					if ( editfields ) {

						changeTableHeader( divval, table );
						// addEditRowInput( rowobj, divval );
					}
	
				}
				
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
	
			var cols;
			cols = getRowParameter( detectTableNS, "rowfields" );

			var strcols = $(this).attr("data-cols");
			if ( strcols ) {
				cols = strcols.split(",");
			}
	
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
			
				// if ( editfields ) {
				//	changeTableHeader( divval, table );
				// }
			}
			
			numdata = numdata + 1 ;

		});
		

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
		
		var pagetitle = mw.config.get("wgPageName");
	
		param.title = pagetitle;
	
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
		// TODO: Handle cols here -> it should reflect in data-cols in the end
		
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

		submitSDIimportJSON( this );
	
	});
	
	$( document ).on( "click", ".submitRow", function() {
	
		console.log( "To be handled" );

	});
	
	
	function submitSDIimportJSON( div, result ){
		
		var param = {};
		var selector = $(div).attr('data-selector');
		var pagetitle = $(div).attr('data-title');

		if ( ! pagetitle ) {
			pagetitle = mw.config.get( "wgPageName" );
		}

		// Get if form
		var container = div;
		
		if ( selector ) {
			container = "#" + selector;
		}
		
		var single = $( container ).data( "single" );
		// Get ref - stored in JSON format
		var ref = $( container ).data( "ref" );

		var data = [];
		var cols = null;
		var rowobj = null;
		var numrows = 0;
		
		var namespace = getNamespaceFromTitle( pagetitle );

		if ( result ) {
			
			var separator = ";"; // TO BE HANDLED
			
			// Surveyjs -> single mode
			cols = Object.keys( result );
			colsVals = {};
			
			for ( var c = 0; c < cols.length; c = c + 1 ) {
				
				var vals = result[ cols[c] ].split( separator );
				vals.map(Function.prototype.call, String.prototype.trim);
				
				if ( vals.length > numrows ) {
					numrows = vals.length;
				}
				
				colsVals[ cols[c] ] = vals;
			}
			
			for ( var n = 0; n < numrows; n = n + 1 ) {
				
				var row = [];
				
				for ( var l = 0; l < cols.length; l = l + 1 ) {

					if ( colsVals[cols[l]][n] ) {
						
						row.push( colsVals[cols[l]][n].trim() );
						
					} else {
						
						row.push( undefined );
					}
				}
				
				data.push( row );
				
			}
			
			
		} else {
			
			// Handsontable
			var instance = tableSDImport[ selector ];
			numrows = instance.countRows();
	
			// Push
			for ( var r = 0; r < numrows; r = r + 1 ) {
				data.push( instance.getDataAtRow( r ) );
			}
			
			cols = instance.getColHeader();
			rowobj = instance.getRowHeader( 0 ); // We assume all rows are the same, so only one			
		} 

		

		// Building JSON to submit
		var meta = {};
		var rowfields = null;
		
		if ( cols ) {
			
			if ( cols.length > 0 && ! cols.every( el => el === null ) ) {

				rowfields = cols;
			}
		}
		
		// Putting single options
		if ( single ) {
			meta.single = true;
		}
		
		if ( ref ) {
			meta.ref = ref;
		}
		
		if ( rowfields && JSON.stringify( rowfields ) != JSON.stringify( getRowParameter( namespace, "rowfields" ) ) ) {
			meta.rowfields = rowfields;
		}
		
		if ( rowobj && rowobj !== getRowParameter( namespace, "rowobject" ) ) {
			meta.rowobject = rowobj;
		}

		var strJSON = prepareStructForJSON( meta, data );
		
		if ( strJSON ) {
		
			param.title = pagetitle;
		
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
		
	}
	
	function changeTableHeader( divval, instance, start ) {
		
		var session;
		var colstart = -1;
		
		if ( start !== undefined ) {
			colstart = start;
		}
		
		var selectorchange = "#"+divval+" th";
		
		$( selectorchange ).dblclick(function (e) {

			e.preventDefault();
			var a = instance.getSelected();
			var b  = instance.getColHeader();
			var headers = instance.getColHeader();
			var value;

			if($(selectorchange).find("input[name='id']").val()) {
				value  = $("#sdpreview th").find("input[name='id']").val();
				headers[session] = value;
				session = a[1];
				headers[a[1]]="<input name='id' type='text' value="+b+"\>";
				instance.updateSettings({
					colHeaders: headers
				});
			} else {
				session = a[0][1];

				if (session > colstart) {
					headers[session]="<input name='id' type='text' value="+b[session]+"\>";
					instance.updateSettings({
						colHeaders: headers
					});
					$(this).find("input[name='id']").focus(); 
				}
			}
		});
	
		$( selectorchange ).change(function (e) {
			
			e.preventDefault();
			
			var a = instance.getSelected();
			var b  = instance.getColHeader();
			var headers = instance.getColHeader();
			var value  = $(this).find("input[name='id']").val();
			headers[session] = value;
			
			rowfields = headers;
			changedRowFields = true;
	
			instance.updateSettings({
				colHeaders: headers
			});
		});
	}
	
	function addEditRowInput( rowobj, divval ) {
	
		$( "#"+divval ).append("<fieldset><legend>" + mw.message( 'sdimport-form-edit-rowobject-label' ).text() + "</legend><input id='rowInput' type='text' value='' ><button class='submitRow'>" + mw.message( 'sdimport-form-edit-rowobject-button' ).text() + "</button></fieldset>");
		if ( rowobj ) {
			document.getElementById("rowInput").value = rowobj;
		}
	}
	
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

			// For now we force only certain properties to be transferred
			if ( meta ) {
				
				if ( meta.hasOwnProperty( "rowfields" ) ) {
					obj.meta.rowfields = meta.rowfields;
				}
				
				if ( meta.hasOwnProperty( "rowobject") ) {
					obj.meta.rowobject = meta.rowobject;
				}
				
				if ( meta.hasOwnProperty( "single") ) {
					obj.meta.single = meta.single;
				}
				
				if ( meta.hasOwnProperty( "ref") ) {
					obj.meta.ref = meta.ref;
				}
			}
			
			obj.data = data;
			
			strJSON = JSON.stringify( obj );
		}
		
		
		return strJSON;
	}
	
	
	/** Prepare a handsontable object with empty content **/
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
		
		return table;
	}
	
	/** Create from from data info. Only for single cases **/
	
	function createFormFromData( data, cols, typefields, readOnlyfields ) {
		
		// TODO: Handling separator config maybe for multiple values
		var separator = ";";

		var json = { "questions": [ ] };
		
		for ( var c = 0; c < cols.length; c++ ) {
			
			var question = {};
			
			question.name = cols[c];
			question.type = "text"; // TODO: This to be changed with typefields
			question.title = cols[c];
			
			if ( readOnlyfields && readOnlyfields.includes( cols[c] ) ) {
				question.readOnly = true;
			}
			
			var vals = [];
			for ( var d = 0; d < data.length; d ++ ) {
				
				
				if ( data[d][c] ) {
				
					vals.push( data[d][c] );
				
				}
			}
			
			question.defaultValue = vals.join( separator );

			json.questions.push( question );
			
		}
		
		return json;
		
	}
	
	/**  Extract default parameters from configuration **/
	function getRowParameter( namespace, param="rowfields" ) {
	
		var parameters = mw.config.get( "wgSDImportDataPage" );
		var paramValue = null;
	
		if ( parameters.hasOwnProperty( namespace ) ) {
			
			if ( parameters[namespace].hasOwnProperty( param ) ) {
				paramValue = parameters[namespace][param];
			}
	
		}
	
		return paramValue;	
	}
	
	
	/** Function for retrieving namespace from title **/
	function getNamespaceFromTitle( pagetitle ) {

		var namespace = -1; //Avoid any value given -> Nothing can be stored in Special Page
		
		if ( pagetitle ) {
			
			var parts = pagetitle.split(":", 2 );
			
			var detectTableNS = null;
			
			if ( parts.length > 1 ) {
				
				detectTableNS = parts[0];
				
				var listNS = mw.config.get( "wgFormattedNamespaces" );
				
				for ( var n in listNS ) {
					
					if ( listNS.hasOwnProperty(n) ) {
						
						if ( listNS[n] === detectTableNS ) {
							namespace = n;
						}
					}
					
				}
			}
						
		}
		
		return namespace;
		
	}

	/** TODO: Redundant, in another file **/
	function ifChangedRowfields( rowfields, changedRowFields ) {
		
		if ( changedRowFields ) {
			rowfields.shift();
		}
		
		return rowfields;
	}
	

}( jQuery, mediaWiki ) );
