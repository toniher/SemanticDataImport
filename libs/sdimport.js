/*global $ document jQuery console mw window wgScriptPath alert location */

//TODO: Handle delimiter
/** Load SpreadSheet **/
(function($) {

	$(document).ready( function() {
	
		var readonly = true;
		var extrarows = 0;
	
		var numdata = 0;
	
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
	
			var strcols = $(this).attr("data-cols");
			var cols = strcols.split(",");
	
			var divval = "SMWData-"+numdata;
			$(this).after("<div id='"+divval+"'>");
	
			$(this).hide();

			if ( $(this).data('edit') ) {
				readonly = false;
			}

			var $container  = $('#'+divval);
	
			$container.handsontable({
				data: celldata,
				readOnly: readonly,
				minSpareRows: extrarows,
				colHeaders: cols,
				contextMenu: true,
				columnSorting: true
			});
	
			if ( $(this).data('edit') ) {
				$container.append("<p class='smwdata-commit' data-selector='#"+divval+"'>Commit</p>");
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
	
		param.title = wgCanonicalNamespace + ":" + wgTitle;
	
		param.action = "sdimport";
		param.format = "json";

		var instance = $( selector ).handsontable('getInstance');
		
		var rows = instance.countRows();

		var data = [];

		// Push
		for ( var r = 0; r < rows; r = r + 1 ) {
			data.push( instance.getDataAtRow( r ) );
		}

		//Let's get data from selector
		param.text = convertData2str( data, param.separator, param.delimiter );

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

}( jQuery ) );
