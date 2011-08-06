<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="tags-holder">

	<?php foreach ($tags as $ind => $tag) { ?>
	
	<div class="row">
		<div class="title">
		<?php
		echo HTML::anchor(Route::get('tags')->uri(array('slug' => $tag->slug)), HTML::chars($tag->value), array('class' => 'tag')), 
		     '<span class="tag-questions">', 
		     $tag->post_count,  
		     __(' questions tagged.'), 
		     '</span>';
		?>
		</div>		
	</div>
	
	<?php } ?>

	<div class="pagination-holder"><?php echo $pagination; ?></div>
</div>