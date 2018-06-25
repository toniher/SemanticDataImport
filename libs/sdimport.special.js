// On file upload
$( "input[name=wpfileupload]" ).change( function( event )
{
	let input = $( this ).get( 0 );
	console.log( input.value );
    var filePath = input.value;
    var allowedExtensions = /(.txt|.csv)$/i;
    if(!allowedExtensions.exec(filePath))
    {
        alert('Please upload file having extensions .txt/.csv/ only.');
        fileInput.value = '';
        return false;
    }
    else
    {
	    if ( input && input.files ) 
		{
			if ( input.files.length > 0 ) 
			{
				
				let infile = input.files[0];
				console.log (infile);

				let reader = new FileReader();

				reader.onloadend = function( fev ) 
				{
					if ( fev.target.readyState == FileReader.DONE ) 
					{ // DONE == 2
						//console.log( "LOADED!");
						//console.log( fev.target.result );
					    let delimiter = document.getElementById("mw-input-wpdelimiter").value;

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
						resu=normalize(resu);
						//console.log(resu);
						var allRows = resu.split(/\r?\n|\r/);
						//console.log(rowCells);

						var table = '<table id="table1">';
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
						    console.log(rowCells);

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
						$("#sdpreview").append(table);
						//console.log(rowCells[rowCell]);
					}
				};
				reader.readAsText(infile);	
			}
			//setSubmitCallback( array( 'SpecialSDImport', 'processCSV' ) );
			//suppressReset(false);		
		}
	}
});
