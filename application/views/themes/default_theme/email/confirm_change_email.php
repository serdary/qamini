Hello <?php echo $username; ?>, 

You changed your email address to <?php echo $new_email; ?>.

Please confirm this action by clicking below link:
 
<?php echo $url; ?> 

Regards,

<?php echo Kohana::$config->load('config.website_url'); ?>