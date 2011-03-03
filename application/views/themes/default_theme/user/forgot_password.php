<div class="post-form">

<h2><?php echo __('Forgot password'); ?></h2>

<form action="" method="post" accept-charset="utf-8">

	<?php include Kohana::find_file('views/themes', $dir_name . '/partials/errors') ?>

	<div class="row-small">
		<label for="email"><?php echo __('Email:') ?></label>
		<input id="email" name="email" type="email" maxlength="300" class="text-input-small" />
	</div>
	
	<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />

	<div class="row-small">
		<input class="form-submit-long" type="submit" value="<?php echo __('Reset Password') ?>" />
	</div>

</form>

</div>