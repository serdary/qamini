<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title><?php echo $title;?></title>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Content-Language" content="en-us" />
	<meta name="keywords" content="<?php echo $meta_keywords; ?>" />
	<meta name="description" content="<?php echo $meta_description; ?>" />

	<?php
	// Add stylesheet files
	foreach($styles as $file)
	{
		echo HTML::style($file), "\n";
	}

	// Add javascript files
	foreach($scripts as $file)
	{
		echo HTML::script($file, NULL, TRUE), "\n";
	}
	?>

</head>

<body>

	<!-- wrapper -->
	<div class="wrapper">
	
		<div class="top-message"><?php echo Message::render(); ?></div>
		
		<!-- header -->
		<div class="header">
			
			<div class="logo">
				<?php echo HTML::anchor('/', 'Qamini'); ?>
			</div>
			
			<div class="search-box">
				<form id="search_form" action="<?php echo URL::site(Route::get('search')->uri(array())) ?>" method="get">
					<input id="query" name="query" type="text" value="" maxlength="300" />
					<input type="submit" value="<?php echo __('Search') ?>" />
				</form>
			</div>
			
			<div class="user-actions">
			<?php if (Auth::instance()->logged_in()) { ?>

			<span class="welcome-text"><?php echo __('Welcome back, '), HTML::anchor(Route::get('profile')
                         ->uri(array('username' => HTML::chars($user->username))), HTML::chars($user->username)); ?></span>
			<span><?php echo HTML::anchor(Route::get('user_ops')->uri(array('action' => 'change_password')), __('Change password')); ?></span>
			<span><?php echo HTML::anchor(Route::get('user')->uri(array('action' => 'signout')), __('Sign out')); ?></span>
			<?php } 
				  else
				  { ?>
			<span><?php echo HTML::anchor(Route::get('user')->uri(array('action' => 'login')), __('Login')); ?></span>
			<span><?php echo HTML::anchor(Route::get('user')->uri(array('action' => 'signup')), __('Sign up')); ?></span>
			<?php } ?>
			</div>
			
		</div>
		<!-- header -->
		
		<!-- navigation-->
		<div class="navigation">
			<span>
				<?php echo HTML::anchor(Route::get('question')->uri(array()), __('Questions'), array('class' => 'nav-item')); ?>
			</span>
			<span>
				<?php echo HTML::anchor(Route::get('tags')->uri(array()), __('Tags'), array('class' => 'nav-item')); ?>
			</span>
			<span>
				<?php echo HTML::anchor(Route::get('question')->uri(array('action' => 'unanswered')), __('Unanswered Questions'), array('class' => 'nav-item')); ?>
			</span>
			<span>
				<?php echo HTML::anchor(Route::get('question')->uri(array('action' => 'ask')), __('Ask a Question'), array('class' => 'nav-item')); ?>
			</span>
			
			<!--<div class="add-question">
			<form id="question_form" action="<?php echo URL::site(Route::get('question')->uri(array('action' => 'ask'))) ?>" method="post">
				<input id="title" name="title" type="text" value="" maxlength="300" />
				<input type="hidden" name="hdn_post_title" id="hdn_post_title" value="1"/>
				<input type="submit" value="<?php echo __('Add') ?>" />
				</form>
			</div>-->
		</div>
		<!-- navigation-->
		
		<!-- main content -->
		<div class="content">
			<?php echo $content;?>
		</div>
		<!-- main content -->
	
		<!-- footer -->
		<div class="footer">			
			
			<div class="footer-navigation">
				<span class="footer-nav-item first">
				<?php echo HTML::anchor(Route::get('static')->uri(array('action' => 'about')), __('About')); ?>
				</span>
				
				<span class="footer-nav-item">
				<?php echo HTML::anchor(Route::get('static')->uri(array('action' => 'help')), __('Help')); ?>
				</span>
				
				<span class="footer-nav-item">
				<?php echo HTML::anchor(Route::get('static')->uri(array('action' => 'contact')), __('Contact')); ?>
				</span>
				
				<!--<span class="footer-nav-item">
				<?php echo HTML::anchor(Route::get('static')->uri(array('action' => 'career')), __('Career')); ?>
				</span>-->
			
				<div class="qamini-credit">
					Powered by <a href="http://qamini.com" target="_blank">Qamini</a> &copy; 2011
				</div>
			
			</div>
			
		</div>
		<!-- footer -->
		
		<!-- Development -->
		<?php if (Kohana::$environment !== Kohana::PRODUCTION) { ?>
			<div class="kohana-development">	
				<?php echo View::factory('profiler/stats') ?>
				<p>$_GET = <?php echo Debug::vars($_GET) ?></p><hr />
				<p>$_POST = <?php echo Debug::vars($_POST) ?></p><hr />
				<p>$_COOKIE = <?php echo Debug::vars($_COOKIE) ?></p><hr />
				<p>$_SESSION = <?php echo Debug::vars(Session::instance()->as_array()) ?></p><hr />
				<p>$_SERVER = <?php echo Debug::vars($_SERVER) ?></p>
			</div>
		<?php } ?>
		<!-- Development -->
	
	</div>
	<!-- wrapper -->
	
</body>
</html>