function delAdjClicked ( event ) {
	var adjID = $( this ).closest('tr').attr('data-adjid');
	$.ajax({
			type: 'post',
			url: bumDKPdeladjurl,
			data: {
				"adjid": adjID
				},
			success: function (data) {
				location.reload();
				},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
				},
			});
	event.stopPropagation();
}

function delItemClicked ( event ) {
	var userID = $( this ).closest('tr').attr('data-userid');
	var charID = $( this ).closest('tr').attr('data-charid');
	var lucyID = $( this ).closest('tr').attr('data-lucyid');
	var raidID = $( this ).closest('tr').attr('data-raidid');
	$.ajax({
			type: 'post',
			url: bumDKPdelitemurl,
			data: {
				"raid_id": raidID,
				"user_id": userID,
				"char_id": charID,
				"lucy_id": lucyID
				},
			success: function (data) {
				location.reload();
				},
			error: function (data) {
				console.log('An error occurred.');
				console.log(data);
				},
			});
	event.stopPropagation();
}

function breakdownReadyFn( jQuery ) {
	$("#dkpdetail").on("click", "span[id^='da_']", delAdjClicked );
	$("#dkpdetail").on("click", "span[id^='di_']", delItemClicked );
}

$( document ).ready( breakdownReadyFn );
