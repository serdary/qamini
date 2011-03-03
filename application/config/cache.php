<?php defined('SYSPATH') or die('No direct script access.');
return array
(
	'apc'      => array
	(
		'driver'             => 'apc',
		'default_expire'     => 3600,
	),
	'file'    => array
	(
		'driver'             => 'file',
		'cache_dir'          => APPPATH.'cache',
		'default_expire'     => 3600,
	),
);