<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="admin-holder questions-holder">

	<?php echo View::factory($theme_dir . 'admin/generic_nav')->render(); ?>

	<br /><br /><br /><br />
		
	<div class="settings-holder">
		
		<div class="setting-add-new">
			<form class="post_setting_form" action="<?php echo URL::site(Route::get('admin')->uri(
				array('directory' => 'admin', 'action' => 'index', 'controller' => 'setting'))) ?>" method="post">
				
			<div><span><?php echo __('Setting Key:'); ?></span>
			<span><input type="text" value="" name="input_setting_key" /></span></div>
			
			<div><span><?php echo __('Setting Value:'); ?></span>
			<span><input type="text" value="" name="input_setting_value" /></span></div>
			
			<input type="hidden" name="hdn_new_setting" value="1" />
			<input type="submit" value="<?php echo __('Add Setting'); ?>">
			
			</form>
		</div>
		
		<?php foreach ($settings as $ind => $setting) { ?>
		<div class="row <?php ?>">
			<form class="post_setting_form" action="<?php echo URL::site(Route::get('admin')->uri(
				array('directory' => 'admin', 'action' => 'index', 'controller' => 'setting'))) ?>" method="post">
			
			<div class="setting-id"><?php echo $setting->id; ?></div>
			<div class="setting-key">
				<input type="text" value="<?php echo $setting->key; ?>" name="input_setting_key" />
			</div>
			<div class="setting-value">
				<input type="text" value="<?php echo $setting->value; ?>" name="input_setting_value" />
			</div>
			<div class="setting-status"><?php echo $setting->setting_status; ?></div>
			
			<input type="hidden" name="hdn_new_setting" value="0" />
			<input type="hidden" name="hdn_setting_id" value="<?php echo $setting->id ?>" />
			<input type="submit" value="<?php echo __('Change'); ?>">
			
			</form>
		</div>
		<?php } ?>
	
	</div>
</div>