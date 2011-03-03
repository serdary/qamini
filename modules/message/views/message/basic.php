<ul id="messages">
	<?php foreach ($messages as $message) { ?>
		<li class="<?php echo $message->type ?>">
			<p><?php echo $message->text ?></p>
		</li>
	<?php } ?>
</ul>
