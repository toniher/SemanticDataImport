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
