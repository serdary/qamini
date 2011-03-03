<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Website Main Config File
 */
return array(

	/**
	 * Email address
	 */
	'email' => 'admin@qamini.com',

	/**
	 * Website Url
	 */
	'website_url' => 'www.qamini.com',

	/**
	 * Website Name
	 */
	'website_name' => 'Qamini',

	/**
	 * Qamini cache driver
	 */
	'cache_driver' => 'file',//apc

	/**
	 * Pass reset link expiration time (in seconds)
	 */
	'reset_password_expiration_time' => 7200,

	/**
	 * Default questions page size
	 */
	'default_questions_page_size' => 10,

	/**
	 * Default question content max chars limit for question listing pages 
	 */
	'default_post_content_truncate_limit' => 120,

	/**
	 * Default tags page size
	 */
	'default_tags_page_size' => 10,

	/**
	 * Default questions page size displayed on profile page
	 */
	'default_profile_questions_page_size' => 2,

	/**
	 * Default answers page size displayed on profile page
	 */
	'default_profile_answers_page_size' => 2,

	/**
	 * Default search page size
	 */
	'default_search_page_size' => 20,

);