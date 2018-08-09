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
		
		var SDIJSONpage = false;
		
		// Detect namespace
		// If JSON namespace
		
		// TODO: Handle alias, etc.
		var detectTableNS = mw.config.get( "wgCanonicalNamespace" );
		var pageTitle = mw.config.get("wgTitle");
		
		if ( detectTableNS !== "" ) {
			pageTitle = detectTableNS + ":" + pageTitle;
		}
		
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
					}
					
					// Endpoint where to add - Put as first child
					$("#mw-content-text").prepend("<div id='"+divval+"'"+singleStr+refStr+">");
				
					// TODO: Handle edit mode
				
					var container  = document.getElementById( divval );

					if ( ! formmode || singleStr === "" ) {
				
						var table = new Handsontable( container, {
							data: celldata.data,
							readOnly: readonly,
							minSpareRows: extrarows,
							colHeaders: cols,
							rowHeaders: rowobj,
							contextMenu: true,
							columnSorting: true
						});
					
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
						console.log( "Handle form mode!" );

						// TODO: Mapping rowfields and values below. Trying to get the types from Namespace configuration
						var json = {
							questions: [
								{
									name: "name",
									type: "text",
									title: "Please enter your name:",
									placeHolder: "Jon Snow",
									isRequired: true
								}, {
									name: "birthdate",
									type: "text",
									inputType: "date",
									title: "Your birthdate:",
									isRequired: true
								}, {
									name: "color",
									type: "text",
									inputType: "color",
									title: "Your favorite color:"
								}, {
									name: "email",
									type: "text",
									inputType: "email",
									title: "Your e-mail:",
									placeHolder: "jon.snow@nightwatch.org",
									isRequired: true,
									validators: [
										{
											type: "email"
										}
									]
								}
							]
						};
						
						formSDImport[divval] = new Survey.Model(json);
						
						formSDImport[divval]
							.onComplete
							.add(function (result) {
								// TODO: Handle API query here
								document
									.querySelector('#'+divval)
									.innerHTML = "result: " + JSON.stringify(result.data);
							});
						
						$("#"+divval).Survey({model: formSDImport[divval]});


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
				
				var linkcontainer = this;
			
				$(linkcontainer).after("<div id='"+divval+"'></div>");
				container = document.getElementById( divval );
				
				// Let's make readOnly false
				readonly = false;

				var pagetitle = null;
				var model = "json";
				var rowobj = null;
				var reflink = null;
				
				var table;
				
				// Let's check if content in title
				if ( $(linkcontainer).data('title') ) {
					pagetitle = $(linkcontainer).data('title');
				}
		
				if ( $(linkcontainer).data('model') ) {
					model = $(linkcontainer).data('model');
				}
								
				if ( $(linkcontainer).data('readonly') ) {
					readonly = true;
				}
				
				if ( $(linkcontainer).data('ref') ) {
					reflink = $(linkcontainer).data('ref');
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

					rowobj = getDefaultCols( pagetitle, "rowobject" );

					var posting = $.post( mw.config.get( "wgScriptPath" ) + "/api.php", param );
					posting.done(function( data ) {

						var celldata = createTableFromJSON( data );
						
						if ( celldata && celldata.hasOwnProperty( "data" ) ) {
							
							// TODO: Replace with getRowParemeter			
							cols = getDefaultCols( pagetitle );
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
					
							// Create Handsontable from content
							table = new Handsontable( container, {
								data: celldata.data,
								readOnly: readonly,
								minSpareRows: extrarows,
								colHeaders: cols,
								rowHeaders: rowobj,
								contextMenu: true,
								columnSorting: true
							});
			
							// Let's store in global variable
							tableSDImport[ divval ] = table;
	
						} else {
							
							// TODO: Replace with getRowParemeter			
							cols = getDefaultCols( pagetitle );
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
		
							editfields = getDefaultCols( pagetitle, "editfields" );
							
							if ( editfields ) {
								console.log( divval );
								changeTableHeader( divval, table );
								// addEditRowInput( rowobj, divval );
							}
			
						}		
						
					})
					.fail( function( data ) {
						alert("Error!");
					});
				
				} else {

					// TODO: Replace with getRowParemeter			
					cols = getDefaultCols( pagetitle );
					celldata = [ [ "", "", "" ] ];
					extrarows = 3;
				
					table = fillEmptyTable( container, divval, celldata, cols, extrarows, readonly );
				
					if ( model === "json" ) {

						var pagetitleStr = "";
						if ( pagetitle ) {
							pagetitleStr = "data-title='"+pagetitle+"'";
						}
						$( container ).append("<p class='smwdata-commit-json' " + pagetitleStr + " data-selector='"+divval+"'>"+mw.message( 'sdimport-commit' ).text()+"</p>");
	
						editfields = getDefaultCols( pagetitle, "editfields" );
						
						if ( editfields ) {
							console.log( divval );
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
	
		var namespace = mw.config.get( "wgCanonicalNamespace" );
		
		var pagetitle = mw.config.get("wgTitle");
		
		if ( namespace !== "" ) {
			pagetitle = namespace + ":" + pagetitle;
		}
	
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

		var param = {};
		var selector = $(this).attr('data-selector');
		
		var pagetitle = $(this).attr('data-title');

		if ( ! pagetitle ) {
			pagetitle = mw.config.get( "wgCanonicalNamespace" ) + ":" + mw.config.get("wgTitle");
		}

		// Get if single
		var single = $( "#" + selector ).data( "single" );
		// Get ref - stored in JSON format
		var ref = $( "#" + selector ).data( "ref" );

		var instance = tableSDImport[ selector ];

		var rows = instance.countRows();

		var data = [];

		// Push
		for ( var r = 0; r < rows; r = r + 1 ) {
			data.push( instance.getDataAtRow( r ) );
		}
		
		var cols = instance.getColHeader();
		var rowobj = instance.getRowHeader( 0 ); // We assume all rows are the same, so only one
		
		// TODO: Handle at least rowobject and single mode as well
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
		
		// TODO: Replace getDefaultCols
		if ( rowfields && JSON.stringify( rowfields ) != JSON.stringify( getDefaultCols( pagetitle ) ) ) {
			meta.rowfields = rowfields;
		}
		
		if ( rowobj && rowobj !== getDefaultCols( pagetitle, "rowobject" ) ) {
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
		
	
	});
	
	$( document ).on( "click", ".submitRow", function() {
	
		console.log( "To be handled" );

	});
	
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

	/** Get default columns from pageTitle **/	
	function getDefaultCols( pageTitle, param="rowfields" ) {
		
		var paramValue = null;
		
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
						
						if ( actualNS.hasOwnProperty( param ) ) {
							
							paramValue = actualNS[ param ];
						}
						
					}
					
				}
			
			}

		}
		
		return paramValue;
		
		
	}
	
	/** TODO: Redundant, in another file **/
	function getRowParameter( namespace, param ) {
	
		var parameters = mw.config.get( "wgSDImportDataPage" );
		var paramValue = null;
	
		if ( parameters.hasOwnProperty( namespace ) ) {
			
			if ( parameters[namespace].hasOwnProperty( param ) ) {
				paramValue = parameters[namespace][param];
			}
	
		}
	
		return paramValue;	
	}

	/** TODO: Redundant, in another file **/
	function ifChangedRowfields( rowfields, changedRowFields ) {
		
		if ( changedRowFields ) {
			rowfields.shift();
		}
		
		return rowfields;
	}
	

}( jQuery, mediaWiki ) );
