<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Main template controller for the application
 * Defines page elements such as meta tags, styles, scripts etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Template
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
abstract class Controller_Template_Main extends Controller_Template {

	/**
	 * Template file location
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Auth instance
	 *
	 * @var object Instance of Auth
	 */
	protected $auth;

	/**
	 * Current user
	 *
	 * @var object Instance of User
	 */
	protected $user;

	/**
	 * User is viewing his/her own user profile page or not
	 *
	 * @var bool
	 */
	protected $same_user_profile;

	/**
	 * Initialize class properties before the controller actions.
	 */
	public function before()
	{
		// Start the session
		Session::instance();

		// Set user token
		$this->get_csrf_token();
			
		// Load Auth instance
		$this->auth = Auth::instance();
			
		Model_Setting::instance()->load_settings();

		$this->template = $this->get_theme_directory() . 'template/main';

		// Call parent's before method
		parent::before();

		// Try to get currently signed in user.
		if (($this->user = $this->auth->get_user()) === FALSE)
		{
			$this->user = new Model_User;
		}

		if($this->auto_render)
		{
			$this->set_template_values();
		}
	}

	/**
	 * Fill in default values for our properties before rendering the output.
	 */
	public function after()
	{
		if($this->auto_render)
		{
			$this->add_style(array('main', 'reset'), TRUE);
			$this->add_js(array('jquery-1.5.min'), TRUE);
		}

		// Run parent's after method
		parent::after();
	}

	/**
	 * Checks if the user is the currently being browsed user
	 *
	 * @param string The username that will be checked
	 */
	protected function check_same_user_profile($username)
	{
		$this->same_user_profile = ($this->user !== null && $this->user->username === $username);
	}

	/**
	 * Returns active theme directory for theme views
	 */
	protected function get_theme_directory()
	{
		return sprintf('themes/%s/', $this->get_active_theme_dir_name());
	}

	/**
	 * Returns active theme directory name
	 *
	 * @return string
	 */
	protected function get_active_theme_dir_name()
	{
		return Model_Setting::instance()->get('active_theme');
	}

	/**
	 * Returns active static files directory name
	 *
	 * @return string
	 */
	protected function get_static_dir_name()
	{
		return Model_Setting::instance()->get('static_files_dir');
	}

	/**
	 * Returns active theme static files directory path
	 *
	 * @return string
	 */
	protected function get_theme_static_directory()
	{
		return sprintf('static/themes/%s/', $this->get_static_dir_name());
	}

	/**
	 * Adds new stylesheet file to the styles array of the main template
	 *
	 * @param array   new stylesheet file names
	 * @param boolean adds items to the top of the array if set true
	 */
	protected function add_style($files, $add_top = FALSE)
	{
		foreach ($files as $file)
		{
			if (!$add_top)
				$this->template->styles[] = $this->get_theme_static_directory() . 'css/' . $file . '.css';
			else
				array_unshift($this->template->styles, $this->get_theme_static_directory(). 'css/' . $file . '.css');
		}
	}

	/**
	 * Adds new javascript file to the scripts array of the main template
	 *
	 * @param array   new js file names
	 * @param boolean adds items to the top of the array if set true
	 */
	protected function add_js($files, $add_top = FALSE)
	{
		foreach ($files as $file)
		{
			if (!$add_top)
				$this->template->scripts[] = $this->get_theme_static_directory() . 'js/' . $file . '.js';
			else
				array_unshift($this->template->scripts, $this->get_theme_static_directory() . 'js/' . $file . '.js');
		}
	}

	/**
	 * Checks if the active user has been visited this question before.
	 * If not, increase this post's view count
	 *
	 * @param Model_Post post instance
	 */
	protected function handle_view_count_of_post(Model_Post $post)
	{
		$visited_posts = Session::instance()->get('visited_posts', array());

		// If user is already visited this question in this session, do not increase view count
		if (array_search($post->id, $visited_posts) !== FALSE)
			return;

		if ($post->increase_view_count())
			$visited_posts[] = $post->id;
			
		Session::instance()->set('visited_posts', $visited_posts);
	}

	/**
	 * Saves a csrf token for the current visitor to prevent csrf attacks.
	 *
	 * @return csrf token of the current user
	 */
	protected function set_csrf_token()
	{
		return Session::instance()->set('token', md5(Request::$user_agent . Session::instance()->id()));
	}

	/**
	 * Returns user's csrf token, if token is not saved in session, saves a new one
	 *
	 * @return csrf token of the current user
	 */
	protected function get_csrf_token()
	{
		if (($token = Session::instance()->get('token', NULL)))
			return $token;
			
		return $this->set_csrf_token();
	}

	/**
	 * Checks csrf token
	 *
	 * @param  string posted token
	 * @param  Route route to redirect if token is not validated
	 */
	protected function check_csrf_token($token = '', $redirect_page = NULL)
	{
		if ($this->get_csrf_token() === $token)
			return;

		if ($redirect_page === NULL)
			$redirect_page = Route::get('question')->uri();
			
		$this->request->redirect($redirect_page);
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Sets template default values such as title, meta tags etc.
	 */
	private function set_template_values()
	{
		$this->template->title            = Kohana::config('config.website_name') . __(' Question & Answer System');
		$this->template->meta_keywords    = __('q2a, question&answer');
		$this->template->meta_description = __('Question & answer website');
		$this->template->content          = '';
		$this->template->styles           = array();
		$this->template->scripts          = array();
		$this->template->bind_global('user', $this->user);
	}
}