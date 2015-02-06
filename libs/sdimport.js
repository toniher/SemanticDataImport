/*global $ document jQuery console mw window wgScriptPath alert location */

/** Load SpreadSheet **/
$(document).ready( function() {

	var readonly = true;
	var extrarows = 0;

	var numdata = 0;

	$('.smwdata').each( function() {

		var celldata = [];

		var text = $(this).text();
		var lines = text.split("\n");
	
		for ( var i = 0; i< lines.length; i++ ) {

			if ( lines[i] !== "" ) {
				var row = lines[i].split("\t");
				celldata.push( row );
			}

		}

		var strcols = $(this).attr("data-cols");
		var cols = strcols.split(",");

		var divval = "SMWData-"+numdata;
		$(this).parent().append("<div id='"+divval+"'>");

		$(this).hide();

		$('#'+divval).handsontable({
			data: celldata,
			readOnly: readonly,
			minSpareRows: extrarows,
			colHeaders: cols,
			contextMenu: true
		});

		numdata = numdata + 1 ;

	});
});


$( document ).on( "click", ".smwdata-commit", function() {

	var param = {};
	var selector = $(this).attr('data-selector');
	param.delimiter=",";
	param.enclosure='"';
	
	//Let's get data from selector
	param.text = convertData2str( $( selector ).handsontable( 'getData' ), param.delimiter, param.enclosure );

	param.title = wgCanonicalNamespace + ":" + wgTitle;

	console.log(param);

	param.action = "smwdata";
	param.format = "json";

	var posting = $.post( wgScriptPath + "/api.php", param );
	posting.done(function( data ) {
		var newlocation = location.protocol + '//' + location.host + location.pathname;
		// Go to page with no reloading (with no reload)
		window.setTimeout( window.location.href = newlocation, 1500);
	})
	.fail( function( data ) {
		alert("Error!");
	});
});


/** @param Array
* return string
**/
function convertData2str ( data, delimiter, enclosure ) {
	var str = "";
	var newArr = [];
	if ( data.length > 0 ) {
		// We put \\n or \\t for ensuring proper conversion afterwards
		for ( var i = 0; i < data.length; i++ ) {
			// TODO: Handle enclosure
			var rowstr = data[i].join(delimiter);
			newArr.push( rowstr );
		}
		str = newArr.join("\n");
	}

	return str;
}




