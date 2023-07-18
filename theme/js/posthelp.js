jQuery(document).ready(function($) {
	//array of regular expressions to test against and the suggested reading if matched
	const discussionTitleSuggestions = [
		{
			regex: /word/,
			suggestionUrl: 'https://www.zotero.org/support/word_processor_integration',
			suggestionText: 'Word Processor Integration'
		},
		{
			regex: /style/,
			suggestionUrl: 'https://github.com/citation-style-language/styles/wiki/Requesting-Styles',
			suggestionText: 'Requesting Styles'
		}
	];

	const commentSuggestions = [
		{
			regex: /similar issue/,
			suggestionUrl: 'https://www.zotero.org/support/word_processor_integration',
			suggestionText: 'Word Processor Integration'
		}
	];

	const discussionInputBox = document.querySelector('input[name="Name"]');
	if (discussionInputBox) {
		discussionInputBox.addEventListener('blur', function(e) {
			let postSuggestions = [];

			const title = $('input[name="Name"]').val();
			for(const suggestion of discussionTitleSuggestions) {
				if (suggestion.regex.test(title)) {
					//pattern found, add suggestion
					const linkHtml = `<a href='${suggestion.suggestionUrl}'>${suggestion.suggestionText}</a>`;
					postSuggestions.push(linkHtml);
				}
			}

			if(postSuggestions.length > 0){
				$('#SuggestedReading').show();
				$('#suggestedReadingList').empty();
				for(var i=0; i<postSuggestions.length; i++){
					$('#suggestedReadingList').append('<li>' + postSuggestions[i] + '</li>');
				}
			} else {
				$('#SuggestedReading').hide();
			}
		});
	}

	//TODO: test comment body before submit to offer suggestions
	// document.querySelector('#Form_Comment textarea').addEventListener();
});