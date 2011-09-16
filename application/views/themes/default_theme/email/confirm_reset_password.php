Hello <?php echo $username; ?>, 


If you forgot your password, please click this link:

<?php echo $url; ?> 

If you did not request to change your password, please ignore this email.

Regards,

<?php echo Kohana::$config->load('config.website_url'); ?>