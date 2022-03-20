$.eqDKP = new Object();

function getMain(id) {
	if ($.eqDKP.charlist[id]) {
	  return $.eqDKP.charlist[id].find(e => e['role'] == 2);
	} else {
		return {name: "Not Found " + id};
	}
}

function getSecond(id) {
	if ($.eqDKP.charlist[id]) {
	  return $.eqDKP.charlist[id].find(e => e['role'] == 1);
	} else {
		return {name: "Not Found " + id};
	}
}

function submitAll( event ) {
	event.preventDefault();
	$.ajax({
		type: 'post',
		dataType: 'json',
		url: eqDKPbulkadj,
		data: JSON.stringify($.eqDKP.adjustments),
		success: function (data) {
			$.eqDKP.adjustments.length = 0;
			displayPreview();
			},
		error: function (data) {
			console.log('An error occurred.');
			console.log(data);
			},
		});
}

function getCharacterData() {
	$.ajax({
		type: 'post',
		url: eqDKPcharlist,
		success: function (data) {
			$.eqDKP.charlist = data;
			},
		error: function (data) {
			console.log('An error occurred.');
			console.log(data);
			},
		});
}

function getAllDKP() {
	$.ajax({
		type: 'post',
		url: eqDKPgetalldkp,
		success: function (data) {
			$.eqDKP.dkp = data;
			},
		error: function (data) {
			console.log('An error occurred.');
			console.log(data);
			},
		});
}

function displayPreview() {
	var adjDisplayElement = $("#adjpreview");
	adjDisplayElement.empty();
	if ($.eqDKP.adjustments.length > 0) {
		// two column display
		var midpoint = Math.floor($.eqDKP.adjustments.length/2);
		var div = $('<div/>', { style: 'width: 395px; float: left; margin: 2px;' });
		var table = createTable(['Character', 'Description', 'Amount', 'Pool']);
		div.append(table);
		for (var i = 0; i < $.eqDKP.adjustments.length; i++) {
			if (i == midpoint && i != 0) {
				// setup a new div
				adjDisplayElement.append(div);
				div = $('<div/>', { style: 'width: 395px; float: left; margin: 2px;' });
				table = createTable(['Character', 'Description', 'Amount', 'Pool']);
				div.append(table);
			}
			var rowCnt = i < midpoint ? i : i-midpoint;
			var rowClass = (rowCnt%2 == 0 ? 'bg1' : 'bg2');
			var user_id = $.eqDKP.adjustments[i].user_id;
			var desc = $.eqDKP.adjustments[i].desc;
			var pool = $.eqDKP.adjustments[i].pool;
			var value = $.eqDKP.adjustments[i].value;
			var adjRow = $('<tr/>', {
					'class': rowClass,
					'data-adjid': i,
					});
			adjRow.append($('<td/>', { 'class': 'adjust' }).append(pool == 0 ? (getMain(user_id).name) : (getSecond(user_id).name)));
			adjRow.append($('<td/>', { 'class': 'adjustdesc' }).append(desc));
			adjRow.append($('<td/>', { 'class': 'adjust' }).append($('<span/>', {
					text: value,
					id: 'ad_' + i,
					'class': 'text-button',
					title: 'Remove Adjustment',
					})));
			adjRow.append($('<td/>', { 'class': 'adjust' }).append(pool == 0 ? 'Main' : 'Second'));
			table.find('tbody:last').append(adjRow);
		}
		adjDisplayElement.append(div);
	}
}

function adjRemoveClicked( event ) {
	var adjID = $( this ).closest('tr').attr('data-adjid');
	$.eqDKP.adjustments.splice(adjID, 1);
	displayPreview();
	event.stopPropagation();
}

function calcValue(adjvalue, adjid, pool) {
	if (pool == 1 && getSecond(adjid) == undefined) {
		return;
	}
	var cVal = $.eqDKP.dkp[adjid][pool];
	var result = new Object();

	// check if it's 'ticks' based
	if (adjvalue.includes('tick')) {
		var numTicks = parseInt(adjvalue);
		result.value = numTicks * (pool == 0 ? mainTick : secondTick);
		result.optdesc = ' (' + numTicks + ' tick' + (numTicks > 1 ? 's' : '') + ' @ ' + (pool == 0 ? mainTick : secondTick) + ' per = ' + result.value + ')';
	// percentage adjustment?
	} else if (adjvalue[adjvalue.length - 1] == '%') {
		var amount = parseInt(adjvalue);
		result.value = Math.floor(cVal * (Math.abs(amount)/100));

		if (amount < 0) {
		// penalties always at least 1 dkp
			if (result.value == 0) {
				result.value = 1;
			}
			result.value *= -1;
		}
		result.optdesc = ' (' + adjvalue + ' of ' + cVal + ' = ' + Math.abs(result.value) + ')';
	} else {
		result.value = parseInt(adjvalue);
		result.optdesc = '';
	}

	// never adjust below 0 DKP
	if (result.value < 0 && cVal == 0) {
		result.value = 0;
		result.optdesc = ' (already at zero)';
	}
	
	return result;
}

function getUserID(charName) {
	var query = charName.toLowerCase();
	var userIDs = Object.keys($.eqDKP.charlist);
	for (var i = 0; i < userIDs.length; i++) {
		var uid = userIDs[i];
		for (var x = 0; x < $.eqDKP.charlist[uid].length; x++) {
			if ($.eqDKP.charlist[uid][x].name.toLowerCase() == query) {
				// have a match, add to data
				return uid;
			}
		}
	}
	return 0;
}

function parseFile( event ) {
	var seenID = new Array();
	var contents = event.target.result;
	var lines = contents.split("\n");
	for (var i = 0; i < lines.length; i++) {
		var charname = "";
		var fileAdj = 0;
		var ltmp = lines[i].replace(/[\s]+/g, ":");
		var columns = ltmp.split(":");
		if (!isNaN(columns[0])) {
			// first column is a number, assume raid dump
			charname = columns[1];
			// starting at end, check columns for a value till
			// we find one that is set or hit column 3 (class)
			for (var x = columns.length - 1; x > 3; x--) {
				if (columns[x]) {
					// found a value
					if (parseInt(columns[x])) {
						fileAdj = columns[x];
						break;
					}
				}
			}
		} else {
			// first column is not a number, assume list of names with possible values
			charname = columns[0];
			if (columns[1]) {
				fileAdj = columns[1];
			}
		}
		// check that we have a charname set and is a valid character
		var uid = 0;
		if (charname) {
			uid = getUserID(charname);
		}
		if (uid && $.inArray(uid, seenID) == -1) {
			seenID.push(uid);
			// valid char add to stored data
			$.eqDKP.fileData.push({
					'user_id': uid,
					'name': charname,
					'value': fileAdj
					});
		}
	}
}

function fileAttached( event ) {
	event.preventDefault();
	var f = event.target.files[0];
	if (f) {
		if (!f.type.match('text.*')) {
			alert(f.name + " is not a valid text file.");
			return;
		} else {
			var r = new FileReader();
			r.onload = parseFile;
			r.readAsText(f);
		}
	} else {
		alert("Failed to load file.");
	}
}

function postSingle(adjvalue, adjid, adjdesc, pool) {
	var valueInfo = calcValue(adjvalue, adjid, pool);
	if (valueInfo == undefined) {
		return;
	}
	$.ajax({
			type: 'post',
			url: eqDKPaddadj,
			data: {
				'user_id': adjid,
				'adjdesc': adjdesc + valueInfo.optdesc,
				'adjpool': pool,
				'adjamnt': valueInfo.value
				},
			success: function (data) {
				$.eqDKP.fileData.length = 0;
				$('#adjid').val("");
				$('#dkp_adjustment')[0].reset();
			},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
			},
		});
}

function adjDKP(adjvalue, adjid, adjdesc, pool) {
	var valueInfo = calcValue(adjvalue, adjid, pool);
	if (valueInfo == undefined) {
		return;
	}
	$.eqDKP.adjustments.push({
			'user_id': adjid,
			'desc': adjdesc + valueInfo.optdesc,
			'pool': pool,
			'value': valueInfo.value,
			});
}

function addAdjustment( event ) {
	event.preventDefault();
	// validate fields are populated
	
	var adjmain = 0;
	var adjsecond = 0;
	if ($('#adjpoolmain').is(':checked')) {
		adjmain = 1;
	}
	if ($('#adjpoolsecond').is(':checked')) {
		adjsecond = 1;
	}
	// figure out which pools to apply adjustment to
	if (!adjmain && !adjsecond) {
		alert('No pools checked, doing nothing');
		return;
	}
	
	var adjvalue = $('#adjvalue').val();
	var adjid = $('#adjid').val();
	var adjdesc = $('#adjdesc').val();
	var adjname = $('#adjname').val();

	if ($('#adjfile').val() && $.eqDKP.fileData.length > 0) {
		// a file was attached and parsed into fileData
		// use that for a bulk adjustment
		// make sure adjdesc is set at a minimum
		if (!adjdesc) {
			alert('Adjustment Description is blank.');
			return;
		}
		// iterate through fileData applying the adjustments
		for (var i = 0; i < $.eqDKP.fileData.length; i++) {
			var uid = $.eqDKP.fileData[i].user_id;
			var fileValue = $.eqDKP.fileData[i].value;
			if (!adjvalue && !fileValue) {
				alert('No adjustment value set in the form or the file for user: ' + $.eqDKP.fileData[i].name);
				return;
			}
			if (adjmain) {
				adjDKP(fileValue ? fileValue : adjvalue, uid, adjdesc, 0);
			}
			if (adjsecond) {
				adjDKP(fileValue ? fileValue : adjvalue, uid, adjdesc, 1);
			}
		}
	} else {
		// no file, individual adjustment, make sure all fields set
		if (!adjvalue || !adjdesc) {
			alert('Either Adjustment Value or Description are blank.');
			return;
		}
		if (!adjid && !adjname) {
			// bulk adjustment to all members
			console.log('bulk adjustment');
			var userIDs = Object.keys($.eqDKP.dkp);
			for (var i = 0; i < userIDs.length; i++) {
				var uid = userIDs[i];
				if (adjmain) {
					adjDKP(adjvalue, uid, adjdesc, 0);
				}
				if (adjsecond) {
					adjDKP(adjvalue, uid, adjdesc, 1);
				}
			}
		} else {
			if (!adjid) {
				console.log('no charID, but name is set, trying to lookup');
				adjid = getUserID(adjname);
				if (!adjid) {
					alert('Unable to find a userID for ' + adjname);
					return;
				}
			}
			console.log('individual adjustment');
			// single user adjustment
			if (adjmain) {
				adjDKP(adjvalue, adjid, adjdesc, 0);
			}
			if (adjsecond) {
				adjDKP(adjvalue, adjid, adjdesc, 1);
			}
		}
	}
	$.eqDKP.fileData.length = 0;
	$('#adjid').val("");
	$('#dkp_adjustment')[0].reset();
	displayPreview();
}

function submitSingle( event ) {
	event.preventDefault();
	// validate fields are populated
	var adjmain = 0;
	var adjsecond = 0;
	if ($('#adjpoolmain').is(':checked')) {
		adjmain = 1;
	}
	if ($('#adjpoolsecond').is(':checked')) {
		adjsecond = 1;
	}
	// figure out which pools to apply adjustment to
	if (!adjmain && !adjsecond) {
		alert('No pools checked, doing nothing');
		return;
	}
	
	var adjvalue = $('#adjvalue').val();
	var adjid = $('#adjid').val();
	var adjdesc = $('#adjdesc').val();
	var adjname = $('#adjname').val();

	// make sure all fields set
	if (!adjvalue || !adjdesc) {
		alert('Either Adjustment Value or Description are blank.');
		return;
	}
	if (!adjid) {
		if (!adjname) {
			alert('Character name not set');
			return;
		}
		adjid = getUserID(adjname);
		if (!adjid) {
			alert('Unable to find a userID for ' + adjname);
			return;
		}
	}
		
	if (adjmain) {
		postSingle(adjvalue, adjid, adjdesc, 0);
	}
	if (adjsecond) {
		postSingle(adjvalue, adjid, adjdesc, 1);
	}

}

function searchChar(request, response) {
	var query = request.term.toLowerCase();
	var userIDs = Object.keys($.eqDKP.charlist);
	var data = new Array();
	for (var i = 0; i < userIDs.length; i++) {
		var uid = userIDs[i];
		for (var x = 0; x < $.eqDKP.charlist[uid].length; x++) {
			if ($.eqDKP.charlist[uid][x].name.toLowerCase().startsWith(query)) {
				// have a match, add to data
				data.push({'label': $.eqDKP.charlist[uid][x].name, 'value': uid});
			}
		}
	}
	response(data);
}

function adjReadyFn( jQuery ) {
	getCharacterData();
	getAllDKP();

	$.eqDKP.adjustments = new Array();
	$.eqDKP.fileData = new Array();
	$("#previewadj").on("click", addAdjustment );
	$("#submitadj").on("click", submitAll );
	$("#submitsingle").on("click", submitSingle );

	$("#adjfile").on("change", fileAttached );

	$("#adjname").autocomplete({
			source: searchChar,
			select: function ( event, ui ) {
				event.preventDefault();
				$( this ).val( ui.item.label );
				$('#adjid').val( ui.item.value );
			},
			focus: function ( event, ui ) {
				event.preventDefault();
				$( this ).val( ui.item.label );
			},
			});

	$(".adjustment-table").on("click", "span[id^='ad_']", adjRemoveClicked );
}

$( document ).ready( adjReadyFn );
