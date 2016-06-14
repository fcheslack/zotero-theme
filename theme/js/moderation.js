jQuery(document).ready(function($) {
	var currentAction = null;
	
	var getTransient = function(){
		return $('#Form_TransientKey').val();
	};

	// Gets the selected IDs as an array.
	var getIDs = function() {
		var IDs = [];
		$('input:checked').each(function(index, element) {
			if ($(element).attr('id') == 'SelectAll')
				return;

			IDs.push($(element).val());
		});
		return IDs;
	};

	/*
	var afterSuccess = function(data) {
		// Figure out the IDs that are currently in the view.
		var rows = [];
		$('#Log tbody tr').each(function(index, element) {
			if ($(element).attr('id') == 'SelectAll')
				return;
			rows.push($(element).attr('id'));
		});
		var rowsSelector = '#' + rows.join(',#');

		// Requery the view and put it in the table.
		$.get(
			window.location.href,
			{'DeliveryType': 'VIEW'},
			function(data) {
				$('#LogTable').html(data);
				setExpander();

				// Highlight the rows that are different.
				var $foo = $('#Log tbody tr').not(rowsSelector);

				$foo.effect('highlight', {}, 'slow');
			});

		// Update the counts in the sidepanel.
		$('.Popin').popin();
	};
	*/

	$('.SpammerButton').click(function(e) {
		e.preventDefault();
		alert('spammerButton clicked');
		var IDs = getIDs().join(',');
		$.ajax(
			gdn.url('/zotero/spammer?logids=' + IDs),
			{
				method:'POST',
				data:{
					'DeliveryType': 'DELIVER_TYPE_BOOL',
					'TransientKey':getTransient()
				}
			}
		).then(function(){
			window.location = window.location;
		});
		
		return false;
	});
});