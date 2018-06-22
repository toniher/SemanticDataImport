// On file upload
$( "input[name=wpfileupload]" ).change( function( event )
{
	let input = $( this ).get( 0 );
	console.log( input );

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
					console.log( "LOADED!");
					console.log( fev.target.result );
					let resu = fev.target.result ;
					//$("#sdpreview").append(resu);
					var allRows = resu.split(/\r?\n|\r/);
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
					    console.log(separator);
					    var rowCells = allRows[singleRow].split(separator);

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
				}
			};
			reader.readAsText(infile);	
		}			
	}
});
