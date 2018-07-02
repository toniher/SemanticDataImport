//if click botton fileupload
$( "input[name=wpfileupload]").change( function( event )
{
	//save data botton upload file into input variable
	let input = $( this ).get( 0 );
	//console.log(input);
	extensionValidation(input);
});

$( "#mw-input-wpdelimiter" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );
});

$( "#mw-input-wpseparator" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );

});

$( "#mw-input-wpnamespace" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	mainCsv( input );
});

$("#sdform form").on( "submit", function(event)
{
	event.preventDefault();
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	let delimiter = document.getElementById("mw-input-wpdelimiter").value;
	let separator = document.getElementById("mw-input-wpseparator").value;
	let namespace = document.getElementById("mw-input-wpnamespace").value;
	let infile = input.files[0];
	mainCsv( input );
	console.log(resultado);console.log(parameter);console.log(delimiter);
	console.log(namespace);console.log(separator);console.log(infile.name);

	let formData = new FormData();
	formData.append( "wpfileupload", input.files[0] );
	formData.append( "wpseparator", separator );
	formData.append( "wpdelimiter", delimiter );
	formData.append( "wpnamespace", namespace );


	//let exec = document.URL;

	$.ajax
	({
		url : "/w/api.php",
		type: "POST",
		data :'file='+input.files[0]+'&separator='+separator+'&delimiter='+delimiter+'&namespace='+namespace,
		processData: true,
		//contentType: false,
			success:function(data, textStatus, jqXHR){
				console.log( data );
				console.log( textStatus );

			},
			error: function(jqXHR, textStatus, errorThrown){
				//if fails
				// TODO: To be handled
				console.log( "error" );
			}
	});
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
						resultado=resultCsw(resu,delimiter,separator);
						var result = resultado,container1 = document.getElementById('sdpreview'),hot1;
						//empty the div to add the new table
						$("#sdpreview").empty();
						//saves the parameter selected in the form in the variable
						let namespace = document.getElementById("mw-input-wpnamespace").value;
						parameter=namespaceCsv(namespace);
						//generates a table with the added variable
						hot1=handsontableTable(result,parameter,container1);
						changeHeader(hot1);
					}
					//If the file length is less than 0, the file is empty and shows an error message
					else
					{
						$("#sdpreview").empty();
						//alert message error
						$("#sdpreview").append("<b>Error, empty file!</b>");
						//alert("Error, empty file!");
					}
				}
				//if the file is not read correctly, it shows an alert with a message of error
				else
				{
					$("#sdpreview").empty();
					//alert("Error");
					$("#sdpreview").append("<b>Error</b>");
				}
			};
			reader.readAsText(infile);
		}
	}
}
/**
*
*/
function resultCsw(resu,delimiter,separator)
{
	if ( separator === "{TAB}" )
	{
		separator = "\t";
	}
	//converts the text into an array and separates it by delimiter and separator, finally saves it in a variable
	var resultado = $.csv.toArrays( resu, { separator: separator, delimiter: delimiter } );//,container1 = document.getElementById('sdpreview'),hot1;
	return resultado;
}

/**
*
*/
function handsontableTable(result,parameter,container1)
{
	//handsontable table
	hot1 = new Handsontable(container1,
	{
		//data to fill the table
	    data: result,
	    //header of the table, parameter with localsettings data
		colHeaders: parameter,
		rowHeaders: true,
	    // adds empty rows to the data source and also removes excessive rows
	    minSpareRows: 0,
	    readOnly: true,
	    afterGetColHeader: function (col, TH) 
	    {
            // nothing for first column
            if (col == -1)
            {
                return;
            }
        }
    });
    return hot1;
}

/**
*
*/
function changeHeader(hot1)
{
	var session;
    $("th").dblclick(function (e) 
    {
   		//console.log("click");
       	e.preventDefault();
       	var a = hot1.getSelected();
      	// startrow, startcol, endrow, endcol
      	// 0, 1, 2, 3
     	var b  = hot1.getColHeader();
        var headers = hot1.getColHeader();
       	var value;
      	if($("th").find("input[name='id']").val())
      	{
          	value  = $("th").find("input[name='id']").val();
          	headers[session] = value;
         	session = a[1];
         	headers[a[1]]="<input name='id' type='text' value="+b+"\>";
        	hot1.updateSettings(
        	{
                colHeaders: headers
            });
    	}
        else
        {
           	session = a[0][1];
            //console.log( session );
            headers[session]="<input name='id' type='text' value="+b[session]+"\>";
            hot1.updateSettings
            ({
                colHeaders: headers
            });
          	$(this).find("input[name='id']").focus(); 
        }
    });

   	$("th").change(function (e)
   	{
      	e.preventDefault();
      	var a = hot1.getSelected();
      	var b  = hot1.getColHeader();
        var headers = hot1.getColHeader();
      	var value  = $(this).find("input[name='id']").val();
       	headers[session] = value;
       	parameter=headers;
       	//console.log(parameter);
        hot1.updateSettings(
        {
            colHeaders: parameter
        });
	});
	return parameter;
}

/**
*
*/
function namespaceCsv(namespace)
{
	var parameters = mw.config.get( "wgSDImportDataPage" );
	var parameter = [];

	if ( namespace === "SDImport" )
	{
		parameter = parameters.SDImport.rowfields;
		parameter.push(parameters.SDImport.rowobject);
	}
	if ( namespace === "JSONData" )
	{
		parameter = parameters.JSONData.rowfields;
		parameter.push(parameters.JSONData.rowobject);
	}
	if ( namespace === "" )
	{
		parameter = true;
	}
	return parameter;
}

//csv preview table function
function extensionValidation(input)
{
	//Save filename with extension into variable
    var filePath = input.value;
 	//Initializate aviable extensions
    var allowedExtensions = /(.txt|.csv)$/i;
    //If file extension is different to file extension prints alert with error message
    if(!allowedExtensions.exec(filePath)) 
    {
    	$("#sdpreview").empty();
    	//This alert informs user the extension error
    	$("#sdpreview").append("<b>Please upload file having extensions .txt/.csv/ only.</b>");
        //alert('Please upload file having extensions .txt/.csv/ only.');
        fileInput.value = '';
        //returns false
        return false;
    }
    //If extension is aviable
    else
    {
    	mainCsv(input);
	}
}