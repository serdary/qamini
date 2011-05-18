<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="admin-holder questions-holder">

	<?php echo View::factory($theme_dir . 'admin/generic_nav')->render(); ?>
		
	<div class="cms-sub-navigation">
		<?php 
		foreach(Helper_View::get_cms_question_url_list() as $link)
			echo $link;
		?>
		
		<br /><br />
		
		<?php 
		foreach(Helper_View::get_cms_answer_url_list() as $link)
			echo $link;
		?>
		
		<br /><br />
		
		<?php 
		foreach(Helper_View::get_cms_comment_url_list() as $link)
			echo $link;
		?>
		
	</div>

	<br /><br />

	<div class="question-count">
		<?php echo $total_posts, ' ', ucfirst(Inflector::plural('post', $total_posts)); ?>
	</div>

	<?php foreach ($posts as $ind => $post) { ?>
	<div class="row row-holder-<?php echo $post->id; ?>">
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
					
			<div class="post-time">
				<?php echo '<span>', __('created: '), '</span>'
				           , $post->get_relative_creation_time(); ?>
			</div>
		
			<div class="post-moderation">
				<?php 
				switch ($post->post_type)
				{
					case Helper_PostType::QUESTION:
						echo HTML::anchor(Route::get('question')->uri(array('action' => 'edit'
							, 'id' => $post->id, 'slug' => $post->slug)), 'Edit');
						break;
					case Helper_PostType::ANSWER:
					    echo HTML::anchor(Route::get('answer')->uri(array('action' => 'edit'
								, 'id' => $post->id, 'question_id' => $post->parent_post_id)), 'Edit');
						break;
					case Helper_PostType::COMMENT:
						break;
				}
				?>
				
				<form class="moderate_form" action="<?php echo URL::site(Route::get('admin_ajax')->uri(array('directory' => 'admin', 'action' => 'postmoderate'))) ?>" method="post">
				<input type="hidden" name="hdn_id" value="<?php echo $post->id ?>" />
				<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
				<span class="result-<?php echo $post->id; ?>"></span>
				
				<?php 
				 echo __('Moderate: '), 
				
					'<select class="moderate-select-' . $post->id . '">
					<option value="' . Helper_PostModeration::APPROVED . '">' . Helper_PostModeration::APPROVED . '</option>
					<option value="' . Helper_PostModeration::DISAPPROVED . '">' . Helper_PostModeration::DISAPPROVED . '</option>
					<option value="' . Helper_PostModeration::DELETED . '">' . Helper_PostModeration::DELETED . '</option>
					<option value="' . Helper_PostModeration::IN_REVIEW . '">' . Helper_PostModeration::IN_REVIEW . '</option>
					<option value="' . Helper_PostModeration::NORMAL . '">' . Helper_PostModeration::NORMAL . '</option>
					</select>';
				?>
				
				<input type="submit" value="<?php echo __("Moderate") ?>" />
				</form>
			</div>
		</div>
	</div>
	<?php } ?>
	
	<div class="pagination-holder"><?php echo $pagination; ?></div>
</div>