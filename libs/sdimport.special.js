var changedRowFields = false;

//if click botton fileupload
$( "input[name=wpfileupload]").change( function( event ) {
	//save data botton upload file into input variable
	let input = $( this ).get( 0 );
	//console.log(input);
	extensionValidation(input);
});

$( "#mw-input-wpdelimiter" ).change( function( event ) {
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );
});

$( "#mw-input-wpseparator" ).change( function( event ) {
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );

});

$( "#mw-input-wpnamespace" ).change( function( event ) {
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );
});

$("#sdform form").on( "submit", function(event) {

	//document.getElementById("#sdform form").disabled = true;
	event.preventDefault();
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	let delimiter = document.getElementById("mw-input-wpdelimiter").value;
	let separator = document.getElementById("mw-input-wpseparator").value;
	let namespace = document.getElementById("mw-input-wpnamespace").value;

	// Handle main namespace
	let checkNS = namespace;
	
	let single = false;
	if ( $( "#mw-input-wpsingle").is(':checked') ) {
		single = true;
	}
	
	let infile = input.files[0];
	//console.log(infile);
	if(infile==null) {
		$("#sdpreview").empty();
		$("#sdpreview").append("<b>Error file</b>");
	}
	else {
		// TODO: Make more explicit resultado
		mainCsv( input );

		let formData = new FormData();
		formData.append( "wpfileupload", input.files[0] );
		formData.append( "wpseparator", separator );
		formData.append( "wpdelimiter", delimiter );
		formData.append( "wpnamespace", namespace );

		var meta = { "app":"SDI", "version":0.1 };
		
		if ( rowfields && JSON.stringify( ifChangedRowfields( rowfields, changedRowFields ) ) != JSON.stringify( getRowParameter( checkNS, 'rowfields' ) ) ) {
			meta.rowfields = ifChangedRowfields( rowfields );
		}
		
		if ( rowobj && rowobj !== getRowParameter( checkNS, 'rowobject' ) ) {
			meta.rowobject = rowobj;
		}
		
		if ( single ) {
			meta.single = single;
		}
		
		var obj = { "meta" : meta, "data" : resultado };
		
		var pageList = [];
		for (var i = 0; i < resultado.length; ++i) {
			
			if ( resultado[i].length > 0 ) {
				
				pageList.push( resultado[i][0] );
			}

		}

		var batch = false;
		console.log( $.unique( pageList  ) );
		if ( $.unique( pageList ).length > 1 ){
			batch = true;
		}
			
		if ( namespace === "_" ) {
			namespace = "";
		}

		let postObj = {};
		postObj.action = "sdimport";
		postObj.format = "json";
		postObj.model = "json";
		postObj.overwrite = true;
		postObj.batch= batch ;
		postObj.text = JSON.stringify( obj );
		postObj.title = namespace;

		$.ajax({
			url : "/w/api.php",
			type: "POST",
			data : postObj,
			processData: true,
			success:function(data, textStatus, jqXHR){

				if(textStatus === "success") {
					$("#sdform").empty();
					$("#sdpreview").empty();
			    	//This alert informs user succes
			    	$("#sdpreview").append("<form><fieldset><b>"+mw.message( 'sdimport-form-submit-success' ).text()+"</b>");
			    	$("#sdpreview").append("<input type='button' value='"+mw.message( 'sdimport-form-back' ).text()+"' onclick='window.location.reload(true)' style='font-family: Arial; font-size: 10 pt'></form></fieldset>");
				}
			},
			error: function(jqXHR, textStatus, errorThrown){
				//if fails
				//TODO: To be handled
				console.log( "error" );
			}
		});
	}
});

function mainCsv(input)
{
    //if input file is type file
    if ( input && input.files ) 
	{
		//console.log(input.files);
		//if file input is isn't empty
		if ( input.files.length > 0 )
		{
			//save data file into variable
			let infile = input.files[0];
			//initializate FileReader function
			let reader = new FileReader();
			reader.onloadend = function( fev )
			{
				//if this file is readable
				if ( fev.target.readyState == FileReader.DONE )
				{
					//saves the reading of the file in a variable
					let resu = fev.target.result ;
					//check if the file is empty
					if(resu.length>0)
					{
						//console.log(fev.target.result.length);
						//saves the delimiter selected in the form in the variable
						let delimiter = document.getElementById("mw-input-wpdelimiter").value;
						//console.log(resu);
						//saves the separator selected in the form in the variable
						let separator = document.getElementById("mw-input-wpseparator").value;
						resultado = resultCsw( resu, delimiter, separator );
						var result = resultado,container1 = document.getElementById('sdpreview'),hot1;
						//empty the div to add the new table
						$("#sdpreview").empty();
						//saves the parameter selected in the form in the variable
						let namespace = document.getElementById("mw-input-wpnamespace").value;
						
						let checkNS = namespace;

						rowfields = getRowParameter( checkNS, 'rowfields' );
						rowobj = getRowParameter( checkNS, 'rowobject' );
						
						//generates a table with the added variable
						hot1 = handsontableTable( result,rowfields,container1,rowobj );
						
						changeTableHeader( "sdpreview", hot1, 0 );

						$( "#sdpreview" ).append("<fieldset><legend>" + mw.message( 'sdimport-form-edit-rowobject-label' ).text() + "</legend><input id='rowInput' type='text' value='rowobj' ><button class='submitRow'>" + mw.message( 'sdimport-form-edit-rowobject-button' ).text() + "</button></fieldset>");
						document.getElementById("rowInput").value = rowobj;
						
					}
					//If the file length is less than 0, the file is empty and shows an error message
					else {
						$("#sdpreview").empty();
						//alert message error
						$("#sdpreview").append("<strong>"+mw.message("sdimport-form-empty-file")+"</strong>");
						//alert("Error, empty file!");
					}
				}
				//if the file is not read correctly, it shows an alert with a message of error
				else {
					$("#sdpreview").empty();
					//alert("Error");
					$("#sdpreview").append("<strong>"+mw.message("sdimport-form-error")+"</strong>");
				}
			};
			reader.readAsText(infile);
		}
	}
}


$( document ).on( "click", ".submitRow", function() {
	
	var result = resultado,container1 = document.getElementById('sdpreview'),hot1;

	// Global rowobj here... TODO: Handling it better
	rowobj = document.getElementById("rowInput").value;
	
	$( "#sdpreview" ).empty();
	addEditRowInput( rowobj, "sdpreview" );

	hot1 = handsontableTable( result, rowfields, container1, rowobj );
	changeTableHeader( "sdpreview", hot1, 0 );

});

/** TODO: This is copy pasted from version in sdimport **/

function addEditRowInput( rowobj, divval ) {
	
	$( "#"+divval ).append("<fieldset><legend>" + mw.message( 'sdimport-form-edit-rowobject-label' ).text() + "</legend><input id='rowInput' type='text' value='' ><button class='submitRow'>" + mw.message( 'sdimport-form-edit-rowobject-button' ).text() + "</button></fieldset>");
	if ( rowobj ) {
		document.getElementById("rowInput").value = rowobj;
	}
}

/**
*
*/
function resultCsw(resu,delimiter,separator) {

	if ( separator === "{TAB}" ){
		separator = "\t";
	}
	//converts the text into an array and separates it by delimiter and separator, finally saves it in a variable
	var resultado = $.csv.toArrays( resu, { separator: separator, delimiter: delimiter } );//,container1 = document.getElementById('sdpreview'),hot1;
	return resultado;
}

/**
*
*/
function handsontableTable(result,rowfields,container1,rowobj) {

	let cols = [ "Page" ];
	if ( rowfields ) {
		cols = cols.concat( rowfields );
	}
	
	//handsontable table
	hot1 = new Handsontable(container1, {
		//data to fill the table
	    data: result,
	    //header of the table, parameter with localsettings data
		colHeaders: cols,
		rowHeaders: rowobj,
	    // adds empty rows to the data source and also removes excessive rows
	    minSpareRows: 0,
	    readOnly: true,
	    afterGetColHeader: function (col, TH) {
            // nothing for first column
            if (col == -1)
            {
                return;
            }
        },
    });
    return hot1;
}


/** TODO: This is copy pasted from version in sdimport **/

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

function ifChangedRowfields( rowfields, changedRowFields ) {
	
	if ( changedRowFields ) {
		rowfields.shift();
	}
	
	return rowfields;
}


//csv preview table function
function extensionValidation(input) {
	//Save filename with extension into variable
    var filePath = input.value;
 	//Initializate aviable extensions
    var allowedExtensions = /(.txt|.csv)$/i;
    //If file extension is different to file extension prints alert with error message
    if(!allowedExtensions.exec(filePath)) {
    	$("#sdpreview").empty();
    	//This alert informs user the extension error
    	$("#sdpreview").append("<strong>"+mw.message("sdimport-form-restrict-extensions")+"</strong>");

        fileInput.value = '';
        //returns false
        return false;
    }
    //If extension is aviable
    else {
    	mainCsv(input);
	}
}