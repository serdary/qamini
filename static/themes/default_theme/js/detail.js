var Detail = function() {
	
	var priv = {
			addCommentSuccess: function(data, parentId) {
				
				if (data.error !== undefined)
				{
					if (data.message === undefined)	return;
					$('.result-' + parentId).text(data.message).addClass('error-div').fadeIn(700);
				}
				else
				{
					var comment = '<div class="comment single-comment-' + data.id + '">' 
					+ $('#content_' + parentId).val().replace(/\n/g, "<br />") 
					+ '<span class="comment-action">' + data.comment_link + '</span></div>';
					
					$('.comment-group-' + parentId).prepend(comment).fadeIn(700);
					
					$('#content_' + parentId).val('');
				}
			},
			
			addCommentError: function(data, parentId) {
				if (data.message === undefined)	return;
				
				$('.result-' + parentId).text(data.message).addClass('error-div').fadeIn(700);
			},

			deleteCommentSuccess: function(data, commentId) {				
				if (data.error !== undefined)
				{
					if (data.message === undefined)	return;
					$('.single-comment-' + commentId).parent().find('.general-results').text(data.message).addClass('error-div').fadeIn(700);
				}
				else
				{
					$('.single-comment-' + commentId).parent().find('.general-results').text(data.message).addClass('success-div').fadeIn(700);
					
					$('.single-comment-' + commentId).remove();
				}
			},
			
			deleteCommentError: function(data, commentId) {
				if (data.message === undefined)	return;
				
				$('.single-comment-' + commentId).parent().find('.general-results').text(data.message).addClass('error-div').fadeIn(700);
			},

			votePostSuccess: function(data, postId, voteType) {				
				if (data.error !== undefined)
				{
					if (data.message === undefined)	return;
					$('.voting-error-' + postId).text(data.message).addClass('error-div').fadeIn(700);
				}
				else
				{					
					var oldValue = parseInt($('.votes-' + postId).text());
					var newValue = (voteType == "1") ? oldValue += 1 : oldValue -= 1;
					$('.votes-' + postId).text(newValue);
				}
			},
			
			votePostError: function(data, postId, voteType) {
				if (data.message === undefined)	return;
				
				$('.voting-error-' + postId).text(data.message).addClass('error-div').fadeIn(700);
			},
			
			ownPostVoteError: function(postId) {				
				$('.voting-error-' + postId).text('You cannot vote on your own posts.').addClass('error-div').fadeIn(700);
			},

			acceptPostSuccess: function(data, postId) {
				if (data.message === undefined)	return;
				
				if (data.error !== undefined)
				{
					$('.voting-error-' + postId).text(data.message).addClass('error-div').fadeIn(700);
				}
				else
				{
					$('.voting-error-' + postId).text(data.message).addClass('success-div').fadeIn(700);
					
					if (data.result == 1)
						$('#accept-post-' + postId).addClass('accepted');
					else if (data.result == 2)
						$('#accept-post-' + postId).removeClass('accepted');
				}
			},
			
			acceptPostError: function(data, postId) {
				if (data.message === undefined)	return;
				
				$('.voting-error-' + postId).text(data.message).addClass('error-div').fadeIn(700);
			},
			
			ownPostAcceptError: function(data, postId) {				
				$('.voting-error-' + postId).text('You cannot accept your own posts.').addClass('error-div').fadeIn(700);
			}
	};
	
	return {
		AddCommentSuccess: function(data, parentId) {
			priv.addCommentSuccess(data, parentId);
		},
		
		AddCommentError: function(data, parentId) {
			priv.addCommentError(data, parentId);
		},
		
		DeleteCommentSuccess: function(data, commentId) {
			priv.deleteCommentSuccess(data, commentId);
		},
		
		DeleteCommentError: function(data, commentId) {
			priv.deleteCommentError(data, commentId);
		},
		
		VotePostSuccess: function(data, postId, voteType) {
			priv.votePostSuccess(data, postId, voteType);
		},
		
		VotePostError: function(data, postId, voteType) {
			priv.votePostError(data, postId, voteType);
		},
		
		OwnPostVoteError: function(postId) {
			priv.ownPostVoteError(postId);
		},
		
		AcceptPostSuccess: function(data, postId) {
			priv.acceptPostSuccess(data, postId);
		},
		
		AcceptPostError: function(data, postId) {
			priv.acceptPostError(data, postId);
		},
		
		OwnPostAcceptError: function(postId) {
			priv.ownPostAcceptError(postId);
		},
		
		DeleteComment: function(parentId, commentId) {
			if (!confirm("Are you sure?"))
				return false;
				
			$.ajax({
				type: "POST",
				url: $('.comment-delete-' + commentId).attr("href"),
				data: "parent_id="+parentId+"&comment_id="+commentId+"&token="+$('.token').val(),
				dataType: "json",

				success: function(data) { Detail.DeleteCommentSuccess(data, commentId) },
				error: function(data) { Detail.DeleteCommentError(data, commentId) },
			});
			
			return false;
		},
		
		VotePost: function(postId, voteType, ownPost, postType) {
			$('.voting-error-' + postId).text('');
			
			if (ownPost === 1)
			{
				Detail.OwnPostVoteError(postId);
				return false;
			}
			
			var elementId = (voteType == 1) ? "#up-" + postId : "#down-" + postId;
			$.ajax({
				type: "POST",
				url: $(elementId).attr("href"),
				data: "post_id="+postId+"&vote_type="+voteType+"&post_type="+postType+"&token="+$('.token').val(),
				dataType: "json",

				success: function(data) { Detail.VotePostSuccess(data, postId, voteType) },
				error: function(data) { Detail.VotePostError(data, postId, voteType) },
			});
			
			return false;
		},
		
		VoteAnswer: function(postId, voteType, ownPost) {
			return Detail.VotePost(postId, voteType, ownPost, 'A');
		},
		
		VoteQuestion: function(postId, voteType, ownPost) {
			return Detail.VotePost(postId, voteType, ownPost, 'Q');
		},
		
		AcceptPost: function(postId, ownPost) {
			$('.voting-error-' + postId).text('');
			
			if (ownPost === 1)
			{
				Detail.OwnPostAcceptError(postId);
				return false;
			}
			
			var elementId = "#accept-post-" + postId;
			$.ajax({
				type: "POST",
				url: $(elementId).attr("href"),
				data: "post_id="+postId+"&token="+$('.token').val(),
				dataType: "json",

				success: function(data) { Detail.AcceptPostSuccess(data, postId) },
				error: function(data) { Detail.AcceptPostError(data, postId) },
			});
			
			return false;
		}
	}
} ();

$(document).ready(function() {	
	
	$(".comment_form").submit(function(e) {
		form = this;
		e.preventDefault();
		
		var parentId = $(this.hdn_parent_id).val();
		$('.result-' + parentId).text('');
		
		$.ajax({
			type: "POST",
			url: this.action,
			data: $(this).serialize(),
			dataType: "json",

			success: function(data) { Detail.AddCommentSuccess(data, parentId) },
			error: function(data) { Detail.AddCommentError(data, parentId) },
		});
		
		return false;         
	});
});