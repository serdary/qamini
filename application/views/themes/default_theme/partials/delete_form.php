<div class="delete-form">

<form action="<?php echo $form_action; ?>" method="post" accept-charset="utf-8"" class="delete_form">

	<input type="submit" value="<?php echo __($button_value) ?>" class="form-submit" />
	
	<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
	<input type="hidden" name="id" value="<?php echo (isset($id)) ? $id : 0; ?>" />
	<input type="hidden" name="parent_id" value="<?php echo (isset($parent_id)) ? $parent_id : 0; ?>" />
</form>

</div>