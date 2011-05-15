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
	'cache_driver' => 'apc',// apc

	/**
	 * Qamini cache time to live
	 */
	'cache_ttl' => 60, //86400, // 3600 * 24 --> 1 day

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
	'default_profile_questions_page_size' => 10,

	/**
	 * Default answers page size displayed on profile page
	 */
	'default_profile_answers_page_size' => 5,

	/**
	 * Default users page size displayed on cms pages
	 */
	'default_users_page_size' => 20,

	/**
	 * Default search page size
	 */
	'default_search_page_size' => 20,

	/**
	 * Default max meta title length
	 */
	'max_meta_title_length' => 80,

	/**
	 * Default max meta description length
	 */
	'max_meta_desc_length' => 200,

	/**
	 * Default site usage. 1 => visitors cannot add questions and answers, 0 => otherwise
	 */
	'login_required_to_add_content' => 1,

);
