<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Ajax Controller for the application
 * Used to handle ajax request actions.
 *
 * @package   qamini
 * @uses      Extends Controller
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
abstract class Controller_Basic_Ajax extends Controller {

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
	 * Holds errors for current request
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * Ctor of Controller_Basic_Ajax.
	 * Checks if the request is an ajax request. If so, checks for the current user.
	 * Otherwise calls invalid action.
	 */
	public function __construct(Request $request, Response $response)
	{
		parent::__construct($request, $response);

		$this->response->headers('Content-Type', 'application/json');

		if (!Request::$current->is_ajax())
		{
			$this->request->action('invalid');
			return;
		}

		// Start the session
		Session::instance();
			
		// Load Auth instance
		$this->auth = Auth::instance();

		// Try to get currently signed in user.
		if (($this->user = $this->auth->get_user()) === FALSE)
		{
			$this->user = new Model_User;
		}

		$this->check_login();

		// Checks token to prevent csrf attacks
		if (isset($_POST))
		{
			$this->check_csrf_token(Arr::get($_POST, 'token', ''));
		}
	}

	/**
	 * Adds invalid request error to response
	 */
	public function action_invalid()
	{
		$this->request->redirect(Route::get('question')->uri());
	}

	/**
	 * If visitor is not logged in, adds an error message to the response
	 */
	protected function check_login()
	{
		if (!$this->auth || !$this->auth->logged_in())
		{
			$this->prepare_error_response(__('Please login.'));
		}
	}

	/**
	 * Prepares an error array with provided text, encodes and adds error text to the response
	 *
	 * @param string error message
	 */
	protected function prepare_error_response($error_msg)
	{
		$this->errors = array('error' => TRUE, 'message' => $error_msg);
		$this->response->body(json_encode($this->errors));
	}

	/**
	 * Returns a csrf token for the current visitor to prevent csrf attacks.
	 *
	 * @return csrf token of the current user
	 */
	protected function get_csrf_token()
	{
		return $token = Session::instance()->get('token', NULL);
	}

	/**
	 * Checks csrf token, if tokens does not match, adds an error to the response
	 *
	 * @param  string posted token
	 */
	protected function check_csrf_token($token = '')
	{
		if ($this->get_csrf_token() !== $token)
		{
			$this->prepare_error_response(__('Invalid Request.'));
		}
	}
}