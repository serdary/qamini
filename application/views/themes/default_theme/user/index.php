<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="user-page-container">

<div class="user-info">
	<div>
		<div class="title"><?php echo __('Username:'); ?></div>
		<div class="username"><?php echo $current_user->username; ?></div>
	</div>
	<div>
		<div class="title"><?php echo __('Reputation:'); ?></div>
		<div class="reputation"><?php echo $current_user->reputation; ?></div>
	</div>
	<div>
		<div class="title"><?php echo __('Question Count:'); ?></div>
		<div class="question-count"><?php echo $current_user->question_count; ?></div>
	</div>
	<div>
		<div class="title"><?php echo __('Answer Count:'); ?></div>
		<div class="answer-count"><?php echo $current_user->answer_count; ?></div>
	</div>
	<div>
		<div class="title"><?php echo __('Comment Count:'); ?></div>
		<div class="comment-count"><?php echo $current_user->comment_count; ?></div>
	</div>
	<div>
		<div class="title"><?php echo __('Achieved Badges:'); ?></div>
		<div class="achieved-badges">
		<?php
		$badgesStr = '';
		if (! Check::isListEmptyOrNull($badges))
		{
			foreach ($badges as $badge)
			{
				$badgesStr .= sprintf("<span class='badge-value'>%s</span>",  $badge->badge_name);
			}
		}
		
		echo Helper_View::get_value($badgesStr, '-');
		?>
		</div>
	</div>
</div>

<div class="questions-holder">

	<div class="question-count">
		<?php echo $total_questions, ' ', ucfirst(Inflector::plural('question', $total_questions)); ?>
	</div>

	<?php foreach ($questions as $ind => $post) { ?>
	<div class="row">
		<div class="question-details">
			<div class="view-count">
				<div class="number"><?php echo $post->format_stat(Helper_StatType::VIEW_COUNT); ?></div>
				<div class="text"><?php echo ' ', ucfirst(Inflector::plural('view', $post->view_count)); ?></div>
			</div>
			<div class="vote-count">
				<div class="number"><?php echo $post->format_stat(Helper_StatType::OVERALL_VOTE); ?></div>
				<div class="text">
				<?php echo ' ', ucfirst(Inflector::plural('vote', $post->up_votes - $post->down_votes)); ?>
				</div>
			</div>
			<div class="answer-count">
				<div class="number"><?php echo $post->format_stat(Helper_StatType::ANSWER_COUNT); ?></div>
				<div class="text">
				<?php echo ' ', ucfirst(Inflector::plural('answer', $post->answer_count)); ?>
				</div>
			</div>
		</div>
		
		<div class="question">
			<div class="title">
			<?php
			echo HTML::anchor(Route::get('question')->uri(array('action' => 'detail'
								, 'id' => $post->id, 'slug' => $post->slug)), $post->title);
			?>
			</div>
			<div class="content"><?php echo $post->content_excerpt(); ?></div>
			
			<?php
				$tags_html = '';
				foreach ($post->get_tags() as $tag)
				{
					$tags_html .= HTML::anchor(Route::get('tags')->uri(array('slug' => $tag->slug)), $tag->value);
				}
				
				if ($tags_html !== '')
				{
					echo '<div class="tags"><span>', __('Tags:') , ' </span>', $tags_html, '</div>';
				}
			?>
		
		</div>
	</div>
	<?php } ?>

	<div class="pagination-holder"><?php echo $pagination_questions; ?></div>
</div>

</div>