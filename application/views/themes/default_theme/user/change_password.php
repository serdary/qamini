<div class="post-form">

<h2><?php echo __('Change Password'); ?></h2>

<form action="" method="post" accept-charset="utf-8">

	<?php include Kohana::find_file('views/themes', $dir_name . '/partials/errors') ?>

	<div class="row-small">
		<label for="old_password"><?php echo __('Current password:') ?></label>
		<input id="old_password" name="old_password" type="password" maxlength="300" class="text-input-small" />
	</div>

	<div class="row-small">
		<label for="password"><?php echo __('New password:') ?></label>
		<input id="password" name="password" type="password" maxlength="300" class="text-input-small" />
	</div>

	<div class="row-small">
		<label for="password_confirm"><?php echo __('Re-enter password:') ?></label>
		<input id="password_confirm" name="password_confirm" type="password" maxlength="300" class="text-input-small" />
	</div>
	
	<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
	
	<div class="row-small">
		<input class="form-submit" type="submit" value="<?php echo __('Change') ?>" />
	</div>

</form>

</div>