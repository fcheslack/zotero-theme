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

	$('.ApproveAsNewDiscussionButton').click(function(e) {
		e.preventDefault();
		console.log("ApproveAsNewDiscussion");
		const ids = getIDs();
		console.log(ids);
		if (ids.length > 1) {
			console.error("More than one ID selected");
			alert("Only one pending log can be converted to a new discussion at a time.");
			return false;
		}
		const logID = ids[0];

		const transientKey = getTransient();

		// Popup the confirm.
		const popupSettings = $.popup.settings;
		$.popup({},
			`<style>
			</style>
			<div id="ApproveAsNewDiscussionDialog">
				<h1>New Discussion Title</h1>
				<form id="ConfirmForm" method="post">
					<div>
						<p>Approve the comment, but change it into a new discussion of its own?</p>
						<input type="text" id="discussionTitleInput" />
						<div class="Buttons">
							<input type="submit" class="Button" />
							<a href="#" class="closeDialog">Cancel</a>
						</div>
					</div>
				</form>
			</div>`
		);
		console.log("binding close");
		$("#ApproveAsNewDiscussionDialog .closeDialog").click(function(e) {
			e.preventDefault();
			$.popup.close(popupSettings);
		});
		console.log("binding Confirm submit");
		$("#ApproveAsNewDiscussionDialog #ConfirmForm").submit(function(e) {
			e.preventDefault();
			console.log("ApproveAsNewDiscussionDialog confirmForm submitted");
			const discussionTitle = $("#discussionTitleInput").val();

			$.ajax(
				gdn.url(`/zotero/approveasdiscussion`),
				{
					method:'POST',
					data:{
						'DeliveryType': 'DELIVER_TYPE_BOOL',
						'TransientKey':transientKey,
						'DiscussionTitle': discussionTitle,
						'LogID': logID,
					}
				}
			).then(function(){
				window.location.reload();
			});
	
		});
		return false;
	});
});