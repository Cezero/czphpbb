$.eqDKP = new Object();

function getMain(id) {
	return $.eqDKP.charlist[id].find(e => e['role'] == 2);
}

function getSecond(id) {
	return $.eqDKP.charlist[id].find(e => e['role'] == 1);
}

function getChar(id, charid) {
	return $.eqDKP.charlist[id].find(e => e['id'] == charid);
}

function makeCharButton(id, text) {
	return $('<span/>', {
		id: 'cb_'+id,
		"class": 'text-button',
		text: getMain(id)['name'],
		title: 'Change Character',
		});
}

function makeTimeButton(id, role, offset, text) {
	return $('<span/>', {
		id: 'td_'+role+'_'+id+'_'+offset,
		"class": 'text-button',
		"data-offset": offset,
		text: text,
		title: 'Modify Attendance',
		});
}

function makeItemButton(id) {
	var ib = $('<img>', {
		src: '/ext/eq_dkp/styles/prosilver/template/images/dagger.png',
		id: 'ib_'+id,
		title: 'Award Item',
		"class": 'text-button',
		height: 10,
		width: 10,
		});
	return ib;
}

function parseName() {
	var row = $( this ).closest('tr');
	var userID = row.attr('data-userid');
	var role = row.attr('data-role');
	var displayElement = $('#cn_'+role+'_'+userID);
	var asChar = $('#ua_'+userID).val();
	$.eqDKP.editchar = 0; // we're no longer picking a name

	displayElement.empty(); // clear it out

	// "as" character only applies to main, ignore this for 2nd mains
	if (role == 2) {
		displayElement.append(makeCharButton(userID));
		if (asChar.length > 0) {
			var mainID = getMain(userID)['id'];
			if (mainID != asChar) {
				var asName = getChar(userID, asChar)['name'];
				displayElement.append($('<span/>', {
							"class": 'charas-text',
							html: '<br/>(as '+asName+')',
									}));
			}
		}
	} else {
		displayElement.append(getSecond(userID)['name']);
	}
}

function parseAttend() {
	var row = $( this ).closest('tr');
	var userID = row.attr('data-userid');
	var role = row.attr('data-role');
	var displayElement = $('#ad_'+role+'_'+userID);
	var raidEnd = Number($('#eq_dkp_raidend').val());
	var time_str = $( '#ut_'+role+'_'+userID ).val();
	displayElement.empty(); // clear it out
	$.eqDKP.editing = 0; // we're no longer editing anyone

	var buttonDiv = $('<div/>', {
			style: "width: 185px; float: left;"
			});
	var addNewButton = 0;
	var addItemButton = 1;
	if (time_str.length > 0) {
		var times = time_str.split(",");
		for (var i = 0; i < times.length; i++) {
			buttonDiv.append(makeTimeButton(userID, role, i, convertNumericToTime(times[i])));
			if (i % 2 == 0) {
				buttonDiv.append('&nbsp;-&nbsp;');
			} else { // only add ; if there are more entries
				if (times.length > i+1) {
					buttonDiv.append('<br/>');
				}
			}
		}
		if (times.length % 2 != 0) {
			buttonDiv.append(makeTimeButton(userID, role, times.length, 'To End'));
		} else {
			if (raidEnd < 0 || (raidEnd > times[times.length - 1])) {
				addNewButton = 1;
			}
		}
	}
	if (time_str.length == 0) {
		addItemButton = 0;
		buttonDiv.append(makeTimeButton(userID, role, 0, 'ABSENT'));
	}

	if(addItemButton && eqDKPraid_id > 0) {
		var itemDiv = $('<div>', {
				style: "float: left;"
				});
		itemDiv.append(makeItemButton(userID));
		displayElement.append(itemDiv);
	}

	displayElement.append(buttonDiv);
	if (addNewButton) {
		var newEntryDiv = $('<div>', {
			style: "float: left;"
			});
		newEntryDiv.append(makeTimeButton(userID, role, times.length, '+'));
		displayElement.append(newEntryDiv);
	}
}

function displayItemList() {
	// only do something if we actually have items
	if ($.eqDKP.itemlist.length > 0) {
		var itemDisplayElement = $( '#itemlist' );
		itemDisplayElement.empty();
		// two column display, half the items on each side
		var midpoint = Math.floor($.eqDKP.itemlist.length/2);
		var div = $('<div/>', { style: 'width: 320px; float: left;' });
		var table = createTable(['Item','Awarded To', 'Cost']);
		div.append(table);
		for (var i = 0; i < $.eqDKP.itemlist.length; i++) {
			if (i == midpoint) {
				// setup a new div
				itemDisplayElement.append(div);
				div = $('<div/>', { style: 'width: 320px; float: left;' });
				table = createTable(['Item','Awarded To', 'Cost']);
				div.append(table);
			}
			var rowCnt = i < midpoint ? i : i-midpoint;
			var rowClass = (rowCnt%2 == 0 ? 'bg1' : 'bg2');
			var itemRow = $('<tr/>', {
					'class': rowClass,
					'data-userid': $.eqDKP.itemlist[i].user_id,
					'data-charid': $.eqDKP.itemlist[i].char_id,
					'data-lucyid': $.eqDKP.itemlist[i].lucy_id,
					});
			var zamLink = $('<a/>', {
				href: eqDKPviewitemurl + '?lucyid=' + $.eqDKP.itemlist[i].lucy_id,
				'data-lucy': 'item=' + $.eqDKP.itemlist[i].lucy_id,
				text: $.eqDKP.itemlist[i].name
				});
			$('<td/>').appendTo(itemRow).append(zamLink);
			var award_str = $.eqDKP.itemlist[i].awarded;
			if ($.eqDKP.itemlist[i].role != 2) {
				// wasn't awarded to main character
				var role = $.eqDKP.itemlist[i].role == 0 ? 'Alt' : '2nd';
				award_str += '<br/><span class="charas-text">(' + $.eqDKP.itemlist[i].main + ' ' + role + ')</span>';
			}
			itemRow.append($('<td/>', { html: award_str }));
			var cost_elem = $('<span/>', {
					text: $.eqDKP.itemlist[i].cost,
					id: 'id_' + $.eqDKP.itemlist[i].lucy_id,
					'class': 'text-button',
					title: 'Delete Item'
					});
			itemRow.append($('<td/>').append(cost_elem));
			table.find('tbody:last').append(itemRow);
		}
		itemDisplayElement.append(div);
	}
	zamTooltip.modifyLinks();
}

function getItemData() {
	if (eqDKPraid_id > 0) {
		$.ajax({
				type: 'post',
				url: eqDKPitemlist,
				data: {
					"raid_id": eqDKPraid_id,
					},
				success: function (data) {
					$.eqDKP.itemlist = data;
					displayItemList();
					},
				error: function (data) {
					console.log('An error occurred.');
					console.log(data);
					},
				});
	}
}

function getCharacterData() {
	$.ajax({
			type: 'post',
			url: eqDKPcharlist,
			success: function (data) {
				$.eqDKP.charlist = data;
				parseAllNames();
				},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
				},
			});
}

function awardItemSubmit( event ) {
	event.preventDefault();
	// validate form items
	var lucyID = Number($("#ai_lucy_id").val());
	var userID = Number($("#ai_user_id").val());
	var cost = Number($("#ai_item_cost").val());

	if (!lucyID || lucyID < 1 || !userID || userID < 1 || !cost || cost < 1) {
		return;
	}
	var formData = $(this).serialize();
	formData += '&raid_id=' + eqDKPraid_id;
	console.log('serialized: '+formData);
	$.ajax({
			type: $(this).attr('method'),
			url: $(this).attr('action'),
			data: formData,
			success: function (data) {
				if (data.result === 'inserted') {
					$('#item_award_dlg').dialog('close');
					getItemData();
				}
				},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
				},
			});
}

function ai_charOnChange( event ) {
	var selOptValue = Number($( this ).val());
	$('#ai_char_id').val(selOptValue);
}

function awardItemDialog( event ) {
	var row = $( this ).closest('tr');
	var userID = row.attr('data-userid');
	var role = row.attr('data-role');
	if (role == 1) {
		defCharID = getSecond(userID)['id'];
	} else {
		defCharID = getMain(userID)['id'];
	}
	// create the new dialog
	var dlg = $('<div>', {
			id: 'item_award_dlg',
			title: 'Award Item',
			});
	$( this ).append(dlg);
	dlg.dialog({
			width: 'auto',
			close: function (ev, ui) {
					$(this).remove();
					$.eqDKP.awarditem = 0;
				}
		}).dialog('open');
	var ai_form = $('<form>', {
			action: eqDKPawarditemurl,
			id: 'award_item_form',
			method: 'POST',
			submit: awardItemSubmit
			});
	ai_form.append($('<input>', {
			id: 'ai_lucy_id',
			type: 'hidden',
			name: 'lucy_id'
			}));
	ai_form.append($('<input>', {
			id: 'ai_pool',
			type: 'hidden',
			name: 'pool',
			value: role == 2 ? 'main' : 'second',
			}));
	ai_form.append($('<input>', {
			id: 'ai_user_id',
			type: 'hidden',
			name: 'user_id',
			value: userID
			}));
	ai_form.append($('<input>', {
			id: 'ai_char_id',
			type: 'hidden',
			name: 'char_id',
			value: defCharID
			}));
	ai_form.append('<label for="item_auto">Item Name:</label>');
	var itemAuto = $('<input>', {
			"class": 'inputbox',
			name: 'item_name',
			id: 'item_auto',
			width: '160'
			});
	itemAuto.autocomplete({
			source: eqDKPitemqueryurl,
			select: function ( event, ui ) {
				event.preventDefault();
				$( this ).val( ui.item.label );
				$('#ai_lucy_id').val(ui.item.value);
			},
			focus: function ( event, ui ) {
				event.preventDefault();
				$( this ).val( ui.item.label );
			},
			});
	ai_form.append(itemAuto);
	ai_form.append('<br/>');
	ai_form.append('<label for="ai_item_cost">Cost:</label>');
	ai_form.append($('<input>', {
			id: 'ai_item_cost',
			"class": 'inputbox',
			name: 'item_cost',
			width: '20'
			}));
	// for main DKP pool, items may be awarded to alts etc
	if (role == 2) {
		ai_form.append('<br/>');
		var select = $('<select/>', {
			id: 'ai_cs',
				});
		select.append(getCharOptionList(userID, defCharID));
		ai_form.append(select);
		select.selectmenu({change: ai_charOnChange});
	}
	ai_form.append('<br/><br/>');
	var buttonDiv = $('<div>', {
			style: "float: right;"
			});
	buttonDiv.append($('<input>', {
			value: 'Award Item',
			type: 'submit',
			name: 'awarditem',
			"class": 'button1'
			}));
	buttonDiv.append('&nbsp;');
	buttonDiv.append($('<input>', {
			value: 'Cancel',
			type: 'submit',
			name: 'cancel',
			click: function(event) {
				event.preventDefault();
				$('#item_award_dlg').dialog('close');
			},
			"class": 'button1'
			}));
	ai_form.append(buttonDiv);
	dlg.append(ai_form);
}

function charOnChange( event ) {
	var id = $( this ).closest('tr').attr('data-userid');
	var selOptValue = Number($( this ).val());
	var asCharElement = $('#ua_'+id);

	asCharElement.val(selOptValue);
}

function timeOnChange( event ) {
	var row = $( this ).closest('tr');
	var id = row.attr('data-userid');
	var role = row.attr('data-role');
	var attendDispElmnt = $('#ad_'+role+'_'+id);
	var offset = $( this ).attr('data-offset');
	var selOptValue = Number($( this ).val());
	var attendElement = $('#ut_'+role+'_'+id);

	var time_str = attendElement.val();
	if (time_str.length > 0) {
		var times = time_str.split(",").map(Number);
		// we're setting start time to -1, that means make them absent
		if (selOptValue == -1 && offset == 0) {
			time_str = '';
		} else {
			if (offset < times.length) {
				// modifying an existing entry
				if (selOptValue < 0) {
					// we're removing a value, delete this and all later entries
					times = times.slice(0, offset);
				} else {
					times[offset] = selOptValue;
					// check if there are later values, and if so, do they start before this entry
					if (offset + 1 < times.length) {
						if (times[offset + 1] <= selOptValue) {
							// they do, remove them
							times = times.slice(0, offset + 1);
						}
					}
				}
			} else {
				// adding a new entry to the end
				times.push(selOptValue);
			}
			time_str = times.join();
		}
	} else {
		if (selOptValue > -1) {
			time_str = selOptValue;
		}
	}
	
	attendElement.val(time_str);
}

function selectedToTop( event, ui ) {
	var menuID = $(event.currentTarget).attr('id');
	var menu = $('ul[aria-labelledby='+menuID+']');
	var active = menu.attr('aria-activedescendant');
	menu.animate({
		scrollTop: ($('#'+active).position().top + menu.scrollTop())
		}, 50);
}

function getCharOptionList(id, selected = -1) {
	var charList = $.eqDKP.charlist[id];
	var output_str = '';
	for (var i = 0; i < charList.length; i++) {
		var sel = '';
		if (selected == charList[i]['id']) {
			sel = ' selected';
		}
		output_str += '<option value="'+charList[i]['id']+'"'+sel+'>'+charList[i]['name']+'</option>';
	}
	return output_str;
}

function editChar() {
	var id = $( this ).closest('tr').attr('data-userid');
	var displayElement = $('#cb_' + id);
	var asChar = $('#ua_'+id).val();

	var sel_option = getMain(id)['id'];
	if (asChar > 0) {
		sel_option = asChar;
	}
	displayElement.empty();
	var select = $('<select/>', {
		id: 'cs_'+id,
			});
	select.append(getCharOptionList(id, sel_option));
	displayElement.append(select);
	$('#cs_'+id).selectmenu({change: charOnChange, close: parseName});
	$('#cs_'+id).selectmenu("open");
}

function editTime() {
	var row = $( this ).closest('tr');
	var id = row.attr('data-userid');
	var role = row.attr('data-role');
	var attendDispElmnt = $('#ad_'+role+'_'+id);
	var offset = $( this ).attr('data-offset');
	var raidStart = Number($('#eq_dkp_raidstart').val());
	var time_str = $('#ut_'+role+'_'+id).val();

	$( this ).empty();
	var first_time;
	var sel_option = -1;
	if (offset == 0) {
		first_time = raidStart;
	}
	// fetch time array
	if (time_str.length > 0) {
		var times = time_str.split(",").map(Number);
		if (offset > 0) {
			var prior = offset - 1;
			if (prior < times.length) {
				first_time = times[prior];
			} else {
				// shouldn't get here, offset somehow larger than array + 1
				first_time = times[times.length - 1];
				offset = times.length;
			}
		}
		if (offset < times.length) {
			sel_option = times[offset];
		}
	}
  var select = $('<select/>', {
		id: 'ts_'+role+'_'+id+'_'+offset,
		"data-offset": offset,
			});
	select.append(getTimeOptionList(first_time, sel_option));
	$( this ).append(select);
	$('#ts_'+role+'_'+id+'_'+offset).selectmenu({change: timeOnChange, close: parseAttend});
	$('#ts_'+role+'_'+id+'_'+offset).selectmenu("open");
}

function parseAllNames() {
	$( ".eq_dkp_name_display" ).each(parseName);
}

function parseAllAttend() {
	$( ".eq_dkp_attend_display" ).each(parseAttend);
}

function itemDeleteClicked( event ) {
	var userID = $( this ).closest('tr').attr('data-userid');
	var charID = $( this ).closest('tr').attr('data-charid');
	var lucyID = $( this ).closest('tr').attr('data-lucyid');
	$.ajax({
			type: 'post',
			url: eqDKPdelitemurl,
			data: {
				"raid_id": eqDKPraid_id,
				"user_id": userID,
				"char_id": charID,
				"lucy_id": lucyID
				},
			success: function (data) {
				getItemData();
				},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
				},
			});
	event.stopPropagation();
}

function itemButtonClicked( event ) {
	var userID = $( this ).closest('tr').attr('data-userid');
	if ($.eqDKP.awarditem > 0) {
		if (userID == $.eqDKP.awarditem) {
			console.log("already awarding an item: " + $.eqDKP.awarditem + " ignoring this event (for now)");
		}
	} else {
		$.eqDKP.awarditem = userID;
		awardItemDialog.call($(this));
	}
	event.stopPropagation();
}

function charButtonClicked( event ) {
	var userID = $( this ).closest('tr').attr('data-userid');
	if ($.eqDKP.editchar > 0) {
		if (userID == $.eqDKP.editchar) {
			console.log("already picking character: " + $.eqDKP.editchar + " ignoring this event (for now)");
		}
	} else {
		$.eqDKP.editchar = userID;
		editChar.call($(this));
	}
	event.stopPropagation();
}

function attendanceEntryClicked( event ) {
	var userID = $( this ).closest('tr').attr('data-userid');
	if ($.eqDKP.editing > 0) { // we're already editing someone
		if (userID == $.eqDKP.editing) {
			console.log("already editing: " + $.eqDKP.editing + " ignoring this event (for now)");
//		} else {
//			parseAttend($.eqDKP.editing);
		}
	} else {
		$.eqDKP.editing = userID;
		editTime.call($(this));
	}
	event.stopPropagation();
}

function setRaidEndOpts () {
	var raidStart = Number($('#eq_dkp_raidstart').val());
	var raidEnd = Number($('#eq_dkp_raidend').val());
	if (raidEnd < raidStart || raidEnd < 0) {
		$('#eq_dkp_raidend').empty().append(getTimeOptionList(raidStart));
		$('#eq_dkp_raidend').selectmenu('refresh');
	}
}

function dateChangeFn () {
	var date_str = $('#eq_dkp_raiddate').val();
	if (date_str) {
		var defTime = eqDKPweekdaystart;
		var raidDate = new Date(date_str);
		if (raidDate.getDay() == 0 || raidDate.getDay() == 6) {
			// weekend
			defTime = eqDKPweekendstart;
		}
		if ($('#eq_dkp_raidstart').val() < 0) {
			$('#eq_dkp_raidstart').empty().append(getTimeOptionList(-1,defTime));
			$('#eq_dkp_raidstart').selectmenu('refresh');
		}
		setRaidEndOpts();
	}
}

function readyFn( jQuery ) {
	getCharacterData();
	getItemData();

	// setup UI elements
	$('#eq_dkp_raiddate').datepicker({
			maxDate: 0
			});
	$('#eq_dkp_raiddate').on('change', dateChangeFn );
	$('#eq_dkp_raidstart').selectmenu({
			open: selectedToTop
			});
	$('#eq_dkp_raidstart').on('change', setRaidEndOpts );
	$('#eq_dkp_raidend').selectmenu({
			open: selectedToTop
			});

	// other setup
	$(".attendance-table").on("click", "span[id^='td_']", attendanceEntryClicked );
	$(".attendance-table").on("click", "span[id^='cb_']", charButtonClicked );
	$(".attendance-table").on("click", "span[id^='id_']", itemDeleteClicked );
	$(".attendance-table").on("click", "img[id^='ib_']", itemButtonClicked );
	parseAllAttend();
}

$(document).ready( readyFn );
