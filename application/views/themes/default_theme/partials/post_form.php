<div class="post-form">

<form id="<?php echo $form_type; ?>_form" action="<?php echo $form_action; ?>" method="post" accept-charset="utf-8">

	<h2><?php echo __($form_title) ?></h2>

	<?php include Kohana::find_file('views', $theme_dir . '/partials/errors') ?>

	<?php if ($form_type === Helper_PostType::QUESTION): ?>
	<div class="row">
		<label for="title"><?php echo __('Title') ?><span>*</span></label>
		<input id="title" name="title" type="text" value="<?php echo HTML::chars($post->title) ?>" maxlength="300" class="text-input" />
	</div>
	<?php endif ?>
	
	<div class="row">
		<label for="content"><?php echo __('Content') ?><span>*</span></label>
		<textarea id="content" name="content"><?php echo HTML::chars($post->content) ?></textarea>
	</div>
	
	<div class="row">
		<label for="notify_user"><?php echo __('Send Notification') ?></label>
		<input id="notify_user" name="notify_user" type="checkbox" <?php if ($notify_user) echo "checked='true'"; ?>/>
	</div>
	
	<?php if (!$user_logged_in): ?>
	<div class="row">
		<label for="created_by"><?php echo __('Name') ?></label>
		<input id="created_by" name="created_by" type="text" value="<?php echo HTML::chars($post->created_by) ?>" maxlength="80" class="text-input" />
	</div>
	
	<div class="row">
		<label for="user_notification_email"><?php echo __('Notification Email') ?></label>
		<input id="user_notification_email" name="user_notification_email" type="text" value="<?php if($post->notify_email !== '0')	echo HTML::chars($post->notify_email) ?>" maxlength="127" class="text-input" />
	</div>
	<?php endif ?>
	
	<?php if ($form_type === Helper_PostType::QUESTION): ?>
	<div class="row">
		<label for="tags"><?php echo __('Tags') ?></label>
		<input id="tags" name="tags" type="text" maxlength="150" class="text-input" value="<?php echo $tag_list ?>"/>
		<div class="tag-info"><?php echo __('Please seperate tags with a comma.') ?></div>
	</div>
	<?php endif ?>
	
	<input type="hidden" name="token" class="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />

	<div class="row">
		<input type="submit" value="<?php echo __($button_value) ?>" class="form-submit" />
	</div>
</form>

</div>