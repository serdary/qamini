<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="questions-holder">

	<div class="question-count">
		<?php echo $total_questions, ' ', ucfirst(Inflector::plural('question', $total_questions)); ?>
	</div>

	<?php
	if (Check::isListEmptyOrNull($posts))
		echo '<div class="no-post">', __('There is no post') , '</div>';
	?>
	
	<?php foreach ($posts as $ind => $post) { ?>
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
								, 'id' => $post->id, 'slug' => $post->slug)), HTML::chars($post->title));
			?>
			</div>
			<div class="content"><?php echo $post->content_excerpt(); ?></div>
			
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
			
			<div class="post-time">
				<?php echo '<span>', __('created: '), '</span>'
				           , $post->get_relative_creation_time(); ?>
			</div>
		
		</div>
	</div>
	<?php } ?>

	<div class="pagination-holder"><?php echo $pagination; ?></div>
</div>