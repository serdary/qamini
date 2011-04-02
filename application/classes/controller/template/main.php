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
		Session::instance();

		$this->set_csrf_token();
			
		$this->auth = Auth::instance();
			
		Model_Setting::instance()->load_settings();

		$this->template = $this->get_theme_directory() . 'template/main';

		parent::before();

		// Try to get currently signed in user.
		if (($this->user = $this->auth->get_user()) === FALSE)
			$this->user = new Model_User;

		if($this->auto_render)
			$this->set_template_values();
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

		parent::after();
	}

	/**
	 * Checks if the user is the currently being browsed user
	 *
	 * @param string username that will be checked
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
	 * @param object instance of Model_Question
	 */
	protected function handle_view_count_of_post(Model_Question $post)
	{
		$visited_posts = Session::instance()->get('visited_posts', array());

		if ($this->user_is_already_visited($post->id, $visited_posts))		return;

		if ($post->increase_view_count())
			$visited_posts[] = $post->id;
			
		Session::instance()->set('visited_posts', $visited_posts);
	}
	
	/**
	 * Checks if a question id is already in visited array
	 * 
	 * @param  int int
	 * @param  array visited questions
	 * @return boolean
	 */
	private function user_is_already_visited($id, &$visited_posts)
	{
		return array_search($id, $visited_posts) !== FALSE;
	}

	/**
	 * Saves a csrf token for the current visitor to prevent csrf attacks and returns the token.
	 *
	 * @return string
	 */
	protected function set_csrf_token()
	{
		return Session::instance()->set('token', md5(Request::$user_agent . Session::instance()->id()));
	}

	/**
	 * Returns user's csrf token, if token is not saved in session, saves a new one
	 *
	 * @return string
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
	 * @param  object route to redirect if token is not validated
	 */
	protected function check_csrf_token($token = '', $redirect_page = NULL)
	{
		if ($this->get_csrf_token() === $token)	return;

		if ($redirect_page === NULL)	$redirect_page = Route::get('question')->uri();
			
		$this->request->redirect($redirect_page);
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Sets template default values such as title, meta tags, styles etc.
	 */
	protected function set_template_values($metas = NULL)
	{
		$this->set_template_metas();
		$this->template->content          = '';
		$this->template->styles           = array();
		$this->template->scripts          = array();
		$this->template->bind_global('user', $this->user);
	}
	
	/**
	 * Prepares webste meta object to fill main template
	 * 
	 * @param string title
	 * @param string description
	 */
	protected function prepare_metas($title, $description)
	{
		$title_postfix = ' | ' . Kohana::config('config.website_name');
		$char_limit = Kohana::config('config.max_meta_title_length') - strlen($title_postfix);
		
		$title = Text::limit_chars(HTML::chars($title), $char_limit) . $title_postfix;
		$description = Text::limit_chars(HTML::chars($description), Kohana::config('config.max_meta_desc_length'));
		
		$this->set_template_metas(new WebsiteMeta($title, $description));
	}

	/**
	 * Sets template metas
	 * 
	 * @param object instance of WebsiteMeta
	 */
	protected function set_template_metas($metas = NULL)
	{
		if ($metas === NULL)
			$metas = WebsiteMeta::generate_default_metas();
			
		$this->template->title            = $metas->get_title();
		$this->template->meta_keywords    = $metas->get_keywords();
		$this->template->meta_description = $metas->get_description();
	}
}