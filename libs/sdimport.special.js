//if click botton fileupload
$( "input[name=wpfileupload]").change( function( event )
{
	//save data botton upload file into input variable
	let input = $( this ).get( 0 );
	//console.log(input);
	csvPreview(input);
});

$( "#mw-input-wpdelimiter" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	csvPreview( input );
});
$( "#mw-input-wpseparator" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	csvPreview( input );

});
$( "#mw-input-wpnamespace" ).change( function( event )
{
	let input = $( "input[name=wpfileupload]" ).get( 0 );
	csvPreview( input );
});
//csv preview table function
function csvPreview(input)
{
	//Save filename with extension into variable
    var filePath = input.value;
 	//Initializate aviable extensions
    var allowedExtensions = /(.txt|.csv)$/i;
    //If file extension is different to file extension prints alert with error message
    if(!allowedExtensions.exec(filePath)) 
    {
    	//This alert informs user the extension error
        alert('Please upload file having extensions .txt/.csv/ only.');
        fileInput.value = '';
        //returns false
        return false;
    }
    //If extension is aviable
    else
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
				//console.log (infile);
				//initializate FileReader function
				let reader = new FileReader();
				reader.onloadend = function( fev )
				{
					//if this file is readable
					if ( fev.target.readyState == FileReader.DONE )
					{
						//console.log( "LOADED!");
						//onsole.log( fev.target.result );
						//check if the file is empty
						if(fev.target.result.length>0)
						{
							//saves the delimiter selected in the form in the variable
						    let delimiter = document.getElementById("mw-input-wpdelimiter").value;
						    //saves the reading of the file in a variable
							let resu = fev.target.result ;
							//console.log(resu);
							//saves the parameter selected in the form in the variable
							let namespace = document.getElementById("mw-input-wpnamespace").value;
							//saves the separator selected in the form in the variable
							let separator = document.getElementById("mw-input-wpseparator").value;

							if ( separator === "{TAB}" ) {
								separator = "\t";
							}
							//converts the text into an array and separates it by delimiter and separator, finally saves it in a variable
							var resultado = $.csv.toArrays( resu, { separator: separator, delimiter: delimiter } ),container1 = document.getElementById('sdpreview'),hot1;
							//empty the div to add the new table
							$("#sdpreview").empty();
					    	//console.log(resultado);
							//console.log(allRows);
							if ( namespace === "SDImport" ) 
							{
								var parameters = mw.config.get( "wgSDImportDataPage" );
								var parameter = parameters.SDImport.rowfields;
								parameter.push(parameters.SDImport.rowobject);
							}
							if ( namespace === "JSONData" )
							{
								var parameters = mw.config.get( "wgSDImportDataPage" );
								var parameter = parameters.JSONData.rowfields;
								parameter.push(parameters.JSONData.rowobject);
							}
							if ( namespace === "" )
							{
								var parameter = true;
							}
							//parameter.push();
							//console.log(parameter);
							//generates a table with the added variable
							var session;
							hot1 = new Handsontable(container1,
							{
								//data to fill the table
							    data: resultado,
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
						           	//console.log(headers);
						           	//console.log("new");
						           	//console.log( a );
						       		//console.log( b );
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
						}
						//If the file length is less than 0, the file is empty and shows an error message
						else
						{
							//alert message error
							alert("Error, empty file!");
						}
					}
					//if the file is not read correctly, it shows an alert with a message of error
					else
					{
						alert("Error");
					}
				};
				reader.readAsText(infile);
			}
		}
	}
}
