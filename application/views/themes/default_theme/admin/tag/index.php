<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="admin-holder tags-holder">

	<?php echo View::factory($theme_dir . 'admin/generic_nav')->render(); ?>

	<br /><br /><br /><br />

	<div class="tags-count">
		<?php echo $total_tags, ' ', ucfirst(Inflector::plural('tag', $total_tags)); ?>
	</div>

	<table>
	<tr>
		<td><?php echo __('id'); ?></td>
		<td><?php echo __('value'); ?></td>
		<td><?php echo __('slug'); ?></td>
		<td><?php echo __('post_count'); ?></td>
		<td><?php echo __('created_by'); ?></td>
		<td><?php echo __('tag_status'); ?></td>
	</tr>
	<?php foreach ($tags as $ind => $tag) { ?>
	
	<tr>
		<form class="post_tag_form" action="<?php echo URL::site(Route::get('admin')->uri(
			array('directory' => 'admin', 'action' => 'index', 'controller' => 'tag'))) ?>" method="post">
			
		<td><?php echo $tag->id; ?></td>
		<td>
		<?php
		echo HTML::anchor(Route::get('tags')->uri(array('slug' => $tag->slug)), $tag->value, array('class' => 'tag'));
		?>
		
		<input type="text" name="tag_value" value="<?php echo $tag->value; ?>" />
		</td>
		<td>
			<input type="text" name="slug" value="<?php echo $tag->slug; ?>" />
		</td>
		<td>
			<input type="text" name="post_count" value="<?php echo $tag->post_count; ?>" />
		</td>
		<td>
			<input type="text" name="created_by" value="<?php echo $tag->created_by; ?>" />
		</td>
		<td>
			<?php echo $tag->tag_status; ?>
			<input type="hidden" name="tag_id" value="<?php echo $tag->id; ?>" />
			<input type="submit" value="<?php echo __('Change Tag'); ?>" />
		</td>	
		</form>	
	</tr>
	
	<?php } ?>
	</table>

	<div class="pagination-holder"><?php echo $pagination; ?></div>
</div>