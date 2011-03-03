<?php if (empty($errors)) return; ?>

<ul class="errors">
	<?php 
	foreach ($errors as $field => $error)
	{
		if (is_array($error))
		{
			foreach ($error as $sub_field => $sub_error)
			{
				//echo '<li>', $sub_field, ': ', $sub_error, '</li>';
				echo '<li>', $sub_error, '</li>';
			}
			continue;
		}
		// DEBUG::vars()
		//echo '<li>', $field, ': ', $error, '</li>';
		echo '<li>', $error, '</li>';
	}
	?>
</ul>