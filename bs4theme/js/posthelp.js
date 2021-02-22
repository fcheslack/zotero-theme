jQuery(document).ready(function($) {
	//map of terms to suggest answers for
	var suggestions = {
		"word": {suggestion:"<a href='https://www.zotero.org/support/word_processor_integration'>Word Processor Integration</a>"},
		"style": {suggestion:"<a href='https://github.com/citation-style-language/styles/wiki/Requesting-Styles'>Requesting Styles</a>"}
	};

	$('input[name="Name"]').blur(function(e){
		var postSuggestions = [];

		var title = $('input[name="Name"]').val();
		var keywords = Object.keys(suggestions);
		for(var i=0; i < keywords.length; i++){
			if(title.indexOf(keywords[i]) != -1){
				postSuggestions.push(suggestions[keywords[i]].suggestion);
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
});