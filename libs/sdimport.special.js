//if click botton fileupload
$( "input[name=wpfileupload]" ).change( function( event )
{
	//save data botton upload file into input variable
	let input = $( this ).get( 0 );
	//console.log(input);
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
        //returns alse
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
					{ // DONE == 2
						//console.log( "LOADED!");
						//onsole.log( fev.target.result );
						//check if the file is empty
						if(fev.target.result.length>0)
						{
							//saves the delimiter selected in the form in the variable
						    let delimiter = document.getElementById("mw-input-wpdelimiter").value;
						    //saves the reading of the file in a variable
							let resu = fev.target.result ;
							//$("#sdpreview").append(resu);
							//Delimiter function
							var normalize = (function() 
							{
							  	var to   = " ", mapping = {};
							 
							  	for(var i = 0, j = delimiter.length; i < j; i++ )
							    mapping[ delimiter.charAt( i ) ] = to.charAt( i );
							 
							 	return function( str ) 
							  	{
							      	var ret = [];
							      	for( var i = 0, j = str.length; i < j; i++ ) 
							      	{
							          	var c = str.charAt( i );
							          	if( mapping.hasOwnProperty( str.charAt( i ) ) )
							              	ret.push( mapping[ c ] );
							          	else
							              	ret.push( c );
							      	}      
							      return ret.join( '' );
							  	}
							})();
							//save the result of the delimiter function in a variable
							resu=normalize(resu);
							//console.log(resu);
							//
							var allRows = resu.split(/\r?\n|\r/),container1 = document.getElementById('example1'),hot1;
							console.log(allRows);
							hot1 = new Handsontable(container1, {
							    data: allRows,
							    //startRows: allRows.length,
							    //startCols: allRows.length,
							    colHeaders: true,
							    minSpareRows: 1
							  });
							/*var table = '<table id="table1">';
							for (var singleRow = 0; singleRow < allRows.length; singleRow++) 
							{
							    if (singleRow === 0) 
							    {
							      	table += '<thead id="table1">';
							      	table += '<tr id="table1">';
							    } 
							    else 
							    {
							      	table += '<tr>';
							    }
							    let separator = document.getElementById("mw-input-wpseparator").value;
							    //console.log(separator);
							    var rowCells = allRows[singleRow].split(separator);
							    console.log(rowCells,"fd");

							    for (var rowCell = 0; rowCell < rowCells.length; rowCell++) 
							    {
							      	if (singleRow === 0) 
							      	{
							        	table += '<th id="table1">';
							        	table += rowCells[rowCell];
							        	table += '</th>';
							      	}	 
							      	else 
							      	{
							        	table += '<td id="table1">';
							        	table += rowCells[rowCell];
							        	table += '</td>';
							      	}
							    }
							    if (singleRow === 0) 
							    {
							      	table += '</tr>';
							      	table += '</thead>';
							      	table += '<tbody>';
							    } 
							    else 
							    {
							      	table += '</tr>';
							    }
							} 
						    table += '</tbody>';
					   	    table += '</table>';
					   	    $("#sdpreview").empty();
							$("#sdpreview").append(table);
							//console.log(rowCells[rowCell]);*/
						}
						else
						{
							alert("Error, empty file!");
						}
					}
					else
					{
						alert("Error");
					}
				};
				reader.readAsText(infile);	
			}
		}
	}
});
