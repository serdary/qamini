<div class="post-form">

<h2><?php echo __('Signup'); ?></h2>

<form action="" method="post" accept-charset="utf-8">

	<?php include Kohana::find_file('views/themes', $dir_name . '/partials/errors') ?>

	<div class="row-small">
		<label for=username><?php echo __('Your username:') ?></label>
		<input id="username" name="username" type="text" value="<?php echo $post['username']; ?>" maxlength="300" class="text-input-small" />
	</div>
	
	<div class="row-small">
		<label for=email><?php echo __('Your email:') ?></label>
		<input id="email" name="email" type="email" value="<?php echo $post['email']; ?>" maxlength="300" class="text-input-small" />
	</div>
	
	<div class="row-small">
		<label for=password><?php echo __('Your password:') ?></label>
		<input id="password" name="password" type="password" maxlength="300" class="text-input-small" />
	</div>
	
	<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />

	<div class="row-small">
		<input class="form-submit" type="submit" value="<?php echo __('Signup') ?>" />
	</div>

</form>

</div>