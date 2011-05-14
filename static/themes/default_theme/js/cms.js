var CMS = function() {
	
	var priv = {
			moderatePostSuccess: function(data, postId) {
				if (data.error !== undefined)
					$('.result-' + postId).text(data.message).addClass('error-div').show();
				else
				{
					$('.result-' + postId).text(data.message).addClass('success-div').show();
					$('.post-holder-' + postId).hide(1500);
				}
			},
			
			moderatePostError: function(data, postId) {
				$('.result-' + postId).text(data.message).addClass('error-div').fadeIn(700);
			}
	};
	
	return {
		ModeratePostSuccess: function(data, postId) {
			priv.moderatePostSuccess(data, postId);
		},
		
		ModeratePostError: function(data, postId) {
			priv.moderatePostError(data, postId);
		}
	}
} ();

$(document).ready(function() {	
	
	$(".post_moderate_form").submit(function(e) {
		form = this;
		e.preventDefault();
		var postId = $(this.hdn_post_id).val();
		var moderationVal = $('.post-moderate-select-' + postId).val();

		$('.result-' + postId).text('');
		
		$.ajax({
			type: "POST",
			url: this.action,
			data: $(this).serialize() + "&moderationVal=" + moderationVal,
			dataType: "json",

			success: function(data) { CMS.ModeratePostSuccess(data, postId) },
			error: function(data) { CMS.ModeratePostError(data, postId) },
		});
		
		return false;         
	});
});