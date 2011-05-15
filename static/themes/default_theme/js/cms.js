var CMS = function() {
	
	var priv = {
			moderateSuccess: function(data, id) {
				if (data.error !== undefined)
					$('.result-' + id).text(data.message).addClass('error-div').show();
				else
				{
					$('.result-' + id).text(data.message).addClass('success-div').show();
					$('.row-holder-' + id).hide(1500);
				}
			},
			
			moderateError: function(data, id) {
				$('.result-' + id).text(data.message).addClass('error-div').fadeIn(700);
			},

			moderateSpamSuccess: function(data, id) {
				if (data.error !== undefined)
					$('.spam-result-' + id).text(data.message).addClass('error-div').show();
				else
				{
					$('.spam-result-' + id).text(data.message).addClass('success-div').show();
				}
			},
			
			moderateSpamError: function(data, id) {
				$('.spam-result-' + id).text(data.message).addClass('error-div').fadeIn(700);
			}
	};
	
	return {
		ModerateSuccess: function(data, id) {
			priv.moderateSuccess(data, id);
		},
		
		ModerateError: function(data, id) {
			priv.moderateError(data, id);
		},
		
		ModerateSpamSuccess: function(data, id) {
			priv.moderateSpamSuccess(data, id);
		},
		
		ModerateSpamError: function(data, id) {
			priv.moderateSpamError(data, id);
		}
	}
} ();

$(document).ready(function() {
	$(".moderate_form").submit(function(e) {
		form = this;
		e.preventDefault();
		var id = $(this.hdn_id).val();
		var moderationVal = $('.moderate-select-' + id).val();

		$('.result-' + id).text('');
		
		$.ajax({
			type: "POST",
			url: this.action,
			data: $(this).serialize() + "&moderationVal=" + moderationVal,
			dataType: "json",

			success: function(data) { CMS.ModerateSuccess(data, id) },
			error: function(data) { CMS.ModerateError(data, id) },
		});
		
		return false;         
	});
	
	$('.user-moderate-select').change(function() {
		var id = this.id;
		var moderationVal = $('.moderate-select-' + id).val();
		
		if (moderationVal == 'spam')
		{
			$('.spam-management-' + id).show();
		}
		else $('.spam-management-' + id).hide();
	});

	$(".spam_moderate_form").submit(function(e) {
		form = this;
		e.preventDefault();
		var id = $(this.hdn_id).val();
		var delete_all_posts = $(this.input_delete_all_posts).is(':checked');
		var mark_anonymous = $(this.input_mark_anonymous_all_posts).is(':checked');
		
		$('.spam-result-' + id).text('');
		
		$.ajax({
			type: "POST",
			url: this.action,
			data: $(this).serialize() + "&delete_all_posts=" + delete_all_posts + "&mark_anonymous=" + mark_anonymous,
			dataType: "json",

			success: function(data) { CMS.ModerateSpamSuccess(data, id) },
			error: function(data) { CMS.ModerateSpamError(data, id) },
		});
		
		return false;         
	});
});