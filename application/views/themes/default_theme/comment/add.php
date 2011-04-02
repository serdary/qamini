<form id="comment_form_<?php echo $parent_id; ?>" class="comment_form" action="<?php echo $form_action; ?>" method="post">

	<div class="form-elements">
		<span class="general-results result-<?php echo $parent_id; ?>"></span>
		<div class="comment-label">
			<label for="content"><?php echo __('Your Comment:') ?></label>
		</div>
		
		<div>
			<textarea class="comment-text" id="content_<?php echo $parent_id; ?>" name="content_<?php echo $parent_id; ?>"></textarea>
		
			<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
			<input type="hidden" name="hdn_parent_id" value="<?php echo $parent_id; ?>" />
		
			<input type="submit" value="<?php echo __("Add Comment") ?>" />
		</div>
	</div>
	
</form>