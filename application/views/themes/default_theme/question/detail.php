<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="detail-holder">

<div class="question-holder">
<div class="vote-info">
	<span class="votes votes-<?php echo $post->id; ?>">
	<?php echo $post->format_stat(Helper_StatType::OVERALL_VOTE); ?>
	</span>
	
	<span class="vote-actions">
		<span>
		<?php 
		 $own_post = (int) ($post->user_id === $user_id);
		 echo HTML::anchor(Route::get('vote')->uri(array('action' => 'vote'
								, 'id' => $post->id)), 'Vote Up'
								, array('onclick' => 'return Detail.VoteQuestion(' . $post->id . ',1,' . $own_post . ')'
								      , 'class' => 'up-vote', 'id' => 'up-' . $post->id));  
		?>
		</span>
		<span>
		<?php
		 echo HTML::anchor(Route::get('vote')->uri(array('action' => 'vote'
								, 'id' => $post->id)), 'Vote Down'
								, array('onclick' => 'return Detail.VoteQuestion(' . $post->id . ',0,' . $own_post . ')'
								      , 'class' => 'down-vote', 'id' => 'down-' . $post->id));
		?> 
		</span> 
	</span>
</div>

<div class="question-details">

<div class="voting-errors voting-error-<?php echo $post->id; ?>"></div>

<h1 class="title">
	<?php
	echo HTML::anchor(Route::get('question')->uri(array('action' => 'detail'
					, 'id' => $post->id, 'slug' => $post->slug)), HTML::chars($post->title));
	?>
</h1>

<div class="content"><?php echo $post->get_post_content(); ?></div>

<!-- Add tags if question has any -->
<?php 
$tags_html = '';
foreach ($post->get_tags() as $tag)
{
	$tags_html .= HTML::anchor(Route::get('tags')->uri(array('slug' => $tag->slug)), HTML::chars($tag->value));
}

if ($tags_html !== '')
{
	echo '<div class="tags"><span>', __('Tags:') , ' </span>', $tags_html, '</div>';
}
?>

<div class="post-info">
<?php 
if ($post_owner_info['id'] !== NULL && $post_owner_info['id'] > 0)
{
	echo __('asked by: '), HTML::anchor(Route::get('profile')
                         ->uri(array('username' => $post_owner_info['created_by'])), $post_owner_info['created_by']);
}
else 
{
	echo __('asked by: '), '<span>', HTML::chars($post_owner_info['created_by']), '</span>';
}
	
echo ', ', $post->get_relative_creation_time();
?>
</div>

<?php 
if ($user_id === $post->user_id)
{
	echo '<div class="user-actions">', 
			HTML::anchor(Route::get('question')->uri(array('action' => 'edit'
						, 'id' => $post->id, 'slug' => $post->slug)), 'Edit');

	echo View::factory($theme_dir . 'partials/delete_form')
	           ->set('id', $post->id)
	           ->set('token', $token)
	           ->set('button_value', __('Delete Question'))
	           ->set('form_action', URL::site(Route::get('question')->uri(array('action' => 'delete', 'id' => $post->id))))
	           ->render();
	                  
	echo '</div>';
}
?>
</div>
	
<?php if ($post->comment_count > 0 || $user_logged_in): ?>

<!-- Show comments of the question -->
<div class="comments-holder">
<div class="comment-group-<?php echo $post->id; ?>">

<?php

foreach ($post->get_comments() as $com)
{
	echo '<div class="comment single-comment-', $com->id, '">', $com->get_post_content();

	echo '<span class="comment-info">';
	$owner_info = $com->get_post_owner_info();
	if ($owner_info['id'] !== NULL && $owner_info['id'] > 0)
	{
		echo __('comment by '), HTML::anchor(Route::get('profile')
                         ->uri(array('username' => $owner_info['created_by'])), $owner_info['created_by']);
	}
	else 
	{
		echo __('comment by '), '<span>', $owner_info['created_by'], '</span>';
	}
	
	echo ', ', $com->get_relative_creation_time();
	echo '</span>';
			
	if ($user_id === $com->user_id)
	{
		echo '<span class="comment-action">', HTML::anchor(Route::get('comment')->uri(array('action' => 'delete'
						, 'id' => $com->id, 'parent_id' => $post->id)), 'Delete'
						, array('onclick' => 'return Detail.DeleteComment(' . $post->id .  ',' . $com->id . ')'
						      , 'class' => 'comment-delete-' . $com->id)), 
			 '</span>';
	}
		
	echo '</div>';
}

echo '</div>';

if ($user_logged_in)
{
	echo '<div class="comment-form">', 
		  View::factory($theme_dir . 'comment/add')
	                  ->set('theme_dir', $theme_dir)
	                  ->set('parent_id', $post->id)
	                  ->set('token', $token)
	                  ->set('form_action', URL::site(Route::get('comment')->uri(array('action' => 'add'))))
	                  ->render(), 
	     '</div>';
}
?>

</div>
<?php endif ?>

</div>

<!-- Display answers -->
<div class="answers-holder">

	<h2 class="total-answer-title">
		<?php 
		echo $post->answer_count, ' ', ucfirst(Inflector::plural('answer', $post->answer_count));
		?>
	</h2>

   <?php foreach ($post->get_answers() as $ind => $answer) { ?>
	<div class="answer">
	
	<div class="vote-info">
		<span class="vote-left-side">
		<span class="votes votes-<?php echo $answer->id; ?>">
		<?php echo $answer->up_votes - $answer->down_votes; ?>
		</span>
		
		<?php 
		// If the answer is not posted by questioner and current user is questioner, show accept button
		if ($user_id === $post->user_id && $user_id !== $answer->user_id)
		{
			$own_post = (int) ($answer->user_id === $user_id);
			$link_class = ($answer->is_accepted()) ? 'accept-post accepted' : 'accept-post';
			
			echo '<span class="accept-post-holder">', 
				 HTML::anchor(Route::get('vote')->uri(array('action' => 'accept'
							, 'id' => $answer->id)), 'Accept'
							, array('onclick' => 'return Detail.AcceptPost(' . $answer->id . ',' . $own_post . ')'
							        , 'class' => $link_class
							        , 'id' => 'accept-post-' . $answer->id)), 
				'</span>';
		}
		elseif ($answer->is_accepted())
		{
			echo '<span class="accept-post-holder"><a class="accept-post accepted">', __('accepted'), '</a></span>';
		}
		?>
		</span>
		<span class="vote-actions">
			<span>
			<?php 
		 	 $own_post = (int) ($answer->user_id === $user_id);
			 echo HTML::anchor(Route::get('vote')->uri(array('action' => 'vote'
									, 'id' => $answer->id)), 'Vote Up'
									, array('onclick' => 'return Detail.VoteAnswer(' . $answer->id . ',1,' . $own_post . ')'
									      , 'class' => 'up-vote', 'id' => 'up-' . $answer->id));
			?>
			</span>
			<span>
			<?php
			 echo HTML::anchor(Route::get('vote')->uri(array('action' => 'vote'
									, 'id' => $answer->id)), 'Vote Up'
									, array('onclick' => 'return Detail.VoteAnswer(' . $answer->id . ',0,' . $own_post . ')'
									      , 'class' => 'down-vote', 'id' => 'down-' . $answer->id)); 
			?>
			</span>
		</span>
	</div>

		<div class="answer-details">
		<div class="voting-errors voting-error-<?php echo $answer->id; ?>"></div>
	
		<div class="answer-content"><?php echo $answer->get_post_content(); ?></div>
		
		<div class="post-info">
		<?php 
		$owner_info = $answer->get_post_owner_info();
		if ($owner_info['id'] !== NULL && $owner_info['id'] > 0)
		{
			echo __('answered by: '), HTML::anchor(Route::get('profile')
		                        ->uri(array('username' => $owner_info['created_by'])), $owner_info['created_by']);
		}
		else 
		{
			echo __('answered by: '), '<span>', $owner_info['created_by'], '</span>';
		}
			
		echo ', ', $answer->get_relative_creation_time();
		?>
		</div>

		<?php 
		if ($user_id === $answer->user_id)
		{
			echo '<div class="answer-actions">', 
			      HTML::anchor(Route::get('answer')->uri(array('action' => 'edit'
								, 'id' => $answer->id, 'question_id' => $post->id)), 'Edit');
								
			echo View::factory($theme_dir . 'partials/delete_form')
	           ->set('id', $answer->id)
	           ->set('parent_id', $post->id)
	           ->set('token', $token)
	           ->set('button_value', __('Delete'))
	           ->set('form_action', URL::site(Route::get('answer')->uri(array('action' => 'delete', 'id' => $answer->id
	           , 'question_id' => $post->id))))
	           ->render();
	           
			echo '</div>';
		}
		?>
		
		</div>

		<?php if ($answer->comment_count > 0 || $user_logged_in): ?>
				
		<div class="answer-comments-holder">
		<div class="comment-group-<?php echo $answer->id; ?>">
		<?php 		
		foreach ($answer->get_comments() as $com)
		{
			echo '<div class="comment single-comment-', $com->id, '">', $com->get_post_content();
			
			echo '<span class="comment-info">';

			$owner_info = $com->get_post_owner_info();
			if ($owner_info['id'] !== NULL && $owner_info['id'] > 0)
			{
				echo __('comment by '), HTML::anchor(Route::get('profile')
		                         ->uri(array('username' => $owner_info['created_by'])), $owner_info['created_by']);
			}
			else 
			{
				echo __('comment by '), '<span>', $owner_info['created_by'], '</span>';
			}
			
			echo ', ', $com->get_relative_creation_time();

			echo '</span>';
			
			if ($user_id === $com->user_id)
			{
			 echo '<span class="comment-action">', HTML::anchor(Route::get('comment')->uri(array('action' => 'delete'
									, 'id' => $com->id, 'parent_id' => $post->id)), 'Delete'
									, array('onclick' => 'return Detail.DeleteComment(' . $post->id .  ',' . $com->id . ')'
									      , 'class' => 'comment-delete-' . $com->id)), 
				  '</span>';
			}
								
			echo '</div>';
		}
		
		echo '</div>';
		
		if ($user_logged_in)
		{
			echo '<div class="comment-form">', 
				  View::factory($theme_dir . 'comment/add')
			                  ->set('theme_dir', $theme_dir)
			                  ->set('parent_id', $answer->id)
			                  ->set('token', $token)
			                  ->set('form_action', URL::site(Route::get('comment')
			                                       ->uri(array('action' => 'add'))))
			                  ->render(), 
			      '</div>';
		}
		?>
		</div>
		<?php endif ?>
	</div>
	<?php } ?>
</div>

<div class="answer-form">
<?php
echo View::factory($theme_dir.'answer/add')
		             ->set('theme_dir', $theme_dir)
		             ->set('user_logged_in', $user_logged_in)
		             ->set('token', $token)
		             ->bind('notify_user', $handled_post['notify_user'])
		             ->bind('errors', $handled_post['errors'])
		             ->set('answer', $current_answer)
		             ->set('form_type', Helper_PostType::ANSWER)
		             ->set('form_action', URL::site(Route::get('question')->uri(
		                   array('action' => 'detail', 'id' => $post->id, 'slug' => $post->slug))))
		             ->set('form_title', __('Add Answer'))
		             ->set('button_value', __('Add'))
		             ->render();
?> 
</div>

</div>