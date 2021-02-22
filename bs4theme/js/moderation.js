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

	$('.SpammerButton').click(function(e) {
		e.preventDefault();
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