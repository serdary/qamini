<div class="post-form">

<h2><?php echo __('Login'); ?></h2>

<form action="" method="post" accept-charset="utf-8">

	<?php include Kohana::find_file('views/themes', $dir_name . '/partials/errors') ?>

	<div class="row-small">
		<label for=username><?php echo __('Your username:') ?></label>
		<input id="username" name="username" type="text" value="<?php echo $post['username']; ?>" maxlength="300" class="text-input-small"/>
	</div>
	
	<div class="row-small">
		<label for=password><?php echo __('Your password:') ?></label>
		<input id="password" name="password" type="password" maxlength="300" class="text-input-small"/>
	</div>
	
	<div class="row-small">
		<label for=remember><?php echo __('Keep me signed in:') ?></label>
		<input type="checkbox" value="<?php echo (!empty($post['remember'])); ?>" name="remember" id="remember">
	</div>

	<div class="row-small">
		<?php echo HTML::anchor(Route::get('user_ops')->uri(array('action' => 'forgot_password'))
                                , 'Forgot password?', array('class' => 'forgot-pass')) ?>
	
		<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
                                
		<input class="form-submit" type="submit" value="<?php echo __('Login') ?>" />
	</div>

</form>

</div>