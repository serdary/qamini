<h2 class="reserved-pages-title"><?php echo __('Help Page'); ?></h2>

<div class="reserved-pages-content">
	<p><?php echo __('CALL TO JOIN CONTENT'); ?></p>
	
<span><?php echo HTML::anchor(Route::get('user')->uri(array('action' => 'login')), __('Login')); ?></span>
<span><?php echo HTML::anchor(Route::get('user')->uri(array('action' => 'signup')), __('Sign up')); ?></span>
</div>