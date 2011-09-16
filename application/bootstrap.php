<?php defined('SYSPATH') or die('No direct script access.');

// -- Environment setup --------------------------------------------------------

// Load the core Kohana class
require SYSPATH.'classes/kohana/core'.EXT;

if (is_file(APPPATH.'classes/kohana'.EXT))
{
	// Application extends the core
	require APPPATH.'classes/kohana'.EXT;
}
else
{
	// Load empty core extension
	require SYSPATH.'classes/kohana'.EXT;
}

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('America/Chicago');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'en_US.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('en-us');

// Set salt cookie for qamini application
Cookie::$salt = 'salt_cookie_4_qamini';

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOHANA_ENV']))
{
	Kohana::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
}

/*
 * Force PHP to show errors if the app is not in production 
 */
if (Kohana::$environment !== Kohana::PRODUCTION && !ini_get('display_errors'))
{
	ini_set('display_errors', 1);
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */

// Base url for qamini installation
Kohana::init(array(
	'base_url' 		=> '/qamini/',
	'index_file' 	=> '',
	'errors'		=> TRUE,
	'profile'  		=> (Kohana::$environment == Kohana::DEVELOPMENT),
	'caching'    	=> (Kohana::$environment == Kohana::PRODUCTION)
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	 'auth'       => MODPATH.'auth',       // Basic authentication
	 'cache'      => MODPATH.'cache',      // Caching with multiple backends
	 'database'   => MODPATH.'database',   // Database access
	 'orm'        => MODPATH.'orm',        // Object Relationship Mapping
	 'message'    => MODPATH.'message',        // flash message
	 'pagination' => MODPATH.'pagination', // Paging of results
     'unittest'   => MODPATH.'unittest',   // Unit testing
// 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
// 'image'      => MODPATH.'image',      // Image manipulation
// 'oauth'      => MODPATH.'oauth',      // OAuth authentication
// 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
));

// Route setup for user actions (signup, login, signout)
Route::set('user', '<action>(/<id>)', array('id' => '[[:digit:]]{1,}', 
                                                   'action' => '(login|signup|signout)'
                                                  )
          )->defaults(array(
          'controller' => 'user',
          ));

// Route setup for user account actions (password change etc.)
Route::set('user_ops', 'user_ops(/<action>(/<id>))')->defaults(
                                                 array('controller' => 'user',
                                                 ));            
                                                 
// http://qamini.com/user/[username]/[action]/
Route::set('profile', 'user/<username>(/<action>)',
array(
		'username' => '([/\pL+/u0-9._-]+)'
))
->defaults(array(
		'controller' => 'user',
));

// Route setup for answer actions
Route::set('answer', 'questions/answer/<action>/<id>/<question_id>', 
                      array('id' => '[[:digit:]]{1,}', 'question_id' => '[[:digit:]]{1,}'))
                                            ->defaults(array(
                                            'controller' => 'answers',
                                            'action'     => 'details',
                                            ));
                                            
// Route setup for question actions
Route::set('question', 'questions(/<action>(/<id>(/<slug>)))', array('id' => '[[:digit:]]{1,}'))->defaults(array(
                                                        'controller' => 'questions',
                                                        ));
                                                        
// Search questions
Route::set('search', 'search(?<query>)')->defaults(array('controller' => 'questions', 'action' => 'search',));
                                                     
// Route setup for comment actions
Route::set('comment', 'comments(/<action>(/<id>(/<parent_id>)))', array('id' => '[[:digit:]]{1,}'))->defaults(array(
                                                        'controller' => 'comments',
                                                        ));
                                                     
// Route setup for tag actions
Route::set('tags', 'tags(/<slug>)')->defaults(array('controller' => 'tags',));  
                     
// Route setup for voting actions
Route::set('vote', 'voting/<action>/<id>')->defaults(array('controller' => 'vote',));
 
// Route setup for site pages, http://qamini.com/site/[page]
Route::set('static', 'site/<action>',
array(
	'action' => '(about|team|help|contact|job|career|join)'
))
->defaults(array(
	'controller' => 'static'
));

// Route setup for error pages
Route::set('error', 'error/<action>', array('action' => '[0-9]++'))
->defaults(array(
    'controller' => 'errors'
));

// Route setup for the homepage of the website
Route::set('homepage', '')->defaults(array(
		'controller' => 'questions',
		'action'     => 'newest',
));

// Route setup for the post administration
Route::set('admin_post', '<directory>/post(/<action>(/<type>(/<moderation>)))',
	array('directory' => '(admin)'))
	->defaults(array(
		'controller' => 'post',
		'action'     => 'index',
	));

/* CMS Routes*/

// Route setup for admin ajax actions
Route::set('admin_ajax', '<directory>/ajax(/<action>(/<id>))',
	array('directory' => '(admin)'))
	->defaults(array(
		'controller' => 'ajax',
		'action'     => 'index',
	));

// Route setup for the user administration
Route::set('admin_user', '<directory>/user(/<action>(/<moderation>))',
	array('directory' => '(admin)'))
	->defaults(array(
		'controller' => 'user',
		'action'     => 'index',
	));

// Route setup for the admin
Route::set('admin', '<directory>(/<controller>(/<action>(/<id>)))',
	array('directory' => '(admin)'))
	->defaults(array(
		'controller' => 'main',
		'action'     => 'index',
	));

// Cache the routes in production
Route::cache(Kohana::$environment === Kohana::PRODUCTION);