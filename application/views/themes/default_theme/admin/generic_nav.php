<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="cms-main-nav">
<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'main')), __('Admin Home')); 
	?>
</span>

<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'post')), __('Post Moderation')); 
	?>
</span>

<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'user')), __('User Moderation')); 
	?>
</span>

<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'setting')), __('Settings Moderation')); 
	?>
</span>

<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'badge')), __('Badge Moderation')); 
	?>
</span>

<span>
	<?php echo HTML::anchor(Route::get('admin')->uri(
		array('directory' => 'admin', 'action' => 'index', 'controller' => 'tag')), __('Tag Moderation')); 
	?>
</span>
</div>