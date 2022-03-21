function pad(num, size) {
	var s = "00000000" + num;
	return s.substr(s.length - size);
}

function convertNumericToTime(i) {
	if (i === '') {
		return '';
	}
	var elements = (i+"").split(".");
	var ampm = 'am';
	var hour = elements[0];
	var min = 0;
	if (elements.length > 1) {
		min = elements[1] * 6;
	}
	hour = hour%24;

	if (hour > 11) {
		ampm = 'pm';
	}
	if (hour > 12) {
		hour = hour - 12;
	}
	if (hour == 0) {
		hour = 12;
		ampm = 'am';
	}
	
	return pad(hour,2) + ":" + pad(min,2) + ampm;
}

function getTimeOptionList(first_value = -1, selected = -1) {
	var output_str = '<option value="-1">Select a Time</option>';
	var i = 0;
	if (first_value > -1) {
		i = first_value;
	}
	var end = i + 24;
	for (i; i < end; i+=.5) {
		var sel = '';
		if (selected == i) {
			sel = ' selected';
		}
		output_str += '<option value="'+i+'"'+sel+'>'+convertNumericToTime(i)+'</option>';
	}
	return output_str;
}

function createTable(colnames) {
	if (colnames.length > 0) {
		var table = $('<table/>', { 'class': 'table2' });
		// assumption that first column aligns left, the rest centered
		// TODO add support for per-column aligment
		var headRow = $('<tr/>').appendTo($('<thead/>').appendTo(table));
		headRow.append($('<th/>', { 'class': 'name', text: colnames[0] }));
		for (var i = 1; i < colnames.length; i++) {
			headRow.append($('<th/>', { 'class':'center', text: colnames[i] }));
		}
		$('<tbody/>').appendTo(table);
		return table;
	}
}

function utilReadyFn( jQuery ) {
	$(".togglelist").on("click", function() {
			var target = "#" + $(this).attr('id') + "list";
			$( target ).toggle();
			});
}

$(document).ready( utilReadyFn );
