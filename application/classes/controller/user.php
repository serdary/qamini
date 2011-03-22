<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini User Controller for all User operations
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_User extends Controller_Template_Main {

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * User profile page action
	 * 
	 * @uses Model_User::get_user_by_username()
	 * @uses Model_User::get_user_posts()
	 */
	public function action_index()
	{
		if (($username = $this->request->param('username', '')) === '')
			$this->request->redirect('');
			
		$this->template->content = View::factory($this->get_theme_directory() . 'user/index')
			->bind('current_user', $user)
			->bind('questions', $questions)
			->bind('total_questions', $total_questions)
			->bind('pagination_questions', $pagination_questions);

		// Try to get the user by username
		if (!($user = ORM::factory('user')->get_user_by_username($username)))
			$this->request->redirect('');

		// Get total questions count
		$total_questions = $user->count_user_posts(Helper_PostType::QUESTION);

		// Prepare questions pagination control
		$pagination_questions = Pagination::factory(array(
			'total_items' => $total_questions,
			'items_per_page' => Kohana::config('config.default_profile_questions_page_size'),
		));

		$questions = $user->get_user_posts($pagination_questions->items_per_page, $pagination_questions->offset);
	}

	/**
	 * Enable login to the website
	 *
	 * @uses Model_User::login()
	 */
	public function action_login()
	{
		// If user is already logged in, redirect user to the homepage
		if ($this->auth->logged_in())
		{
			Message::set(Message::NOTICE, __('You are already logged in'));

			// Redirect to user profile
			$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));
		}

		// Show login form
		$this->template->content = View::factory($this->get_theme_directory() . 'user/login')
			->set('dir_name', $this->get_active_theme_dir_name())
			->set('token', $this->get_csrf_token())
			->bind('post', $post)
			->bind('errors', $errors);

		// Return if form is not submitted
		if (!$_POST)	return;

		$post = $_POST;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''), '');

		// TODO: use validation object.
		if(!isset($post['username']) || $post['username'] === '')
		{
			$errors[] = array('username' => 'Username is required.');
		}
		if (!isset($post['password']) || $post['password'] === '')
		{
			$errors[] = array('password' => 'Password is required.');
		}

		if (!empty($errors))
			return;

		$remember = isset($_POST['remember']);

		// Login the user
		if ($this->user->login($post, $remember))
		{
			Message::set(Message::SUCCESS, __('Welcome back, ') . $this->user->username);
			$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));
		}

		$errors = array('username_password' => 'Incorrect username or password.');
	}

	/**
	 * Signout action for the user
	 *
	 * @uses Auth::logged_in()
	 * @uses Auth::logout()
	 */
	public function action_signout()
	{
		// If user is not signed in, don't try to signout again.
		if (!$this->auth->logged_in())
		{
			Message::set(Message::NOTICE, __('You are already logged out.'));
			$this->request->redirect('');
		}

		$this->auth->logout();

		Message::set(Message::SUCCESS, __('You are successfully logged out.'));
		$this->request->redirect('');
	}

	/* Signup Action Methods */

	/**
	 * Handles new user signups to the website
	 *
	 * @uses Auth::logged_in()
	 * @uses Auth::force_login()
	 * @uses Model_User::signup()
	 */
	public function action_signup()
	{
		// If the user is already logged in, redirect the user to user homepage
		if ($this->auth->logged_in())
		{
			Message::set(Message::NOTICE, __('You are logged in, please log out.'));
			$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));
		}

		// Show signup form
		$this->template->content = View::factory($this->get_theme_directory() . 'user/signup')
			->set('dir_name', $this->get_active_theme_dir_name())
			->set('token', $this->get_csrf_token())
			->bind('post', $post)
			->bind('errors', $errors);

		// Return if form is not submitted
		if (!$_POST)	return;

		$post = $_POST;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''), '');

		// TODO: use validation object.
		if (!isset($post['password']) || $post['password'] === '')
		{
			$errors = array('password' => 'Password is required.');
			return;
		}

		// Try to sign the user up
		try {
			if ($this->user->signup($post, $this->get_theme_directory()))
			{
				// Automatically log the user in
				$this->auth->force_login($post['username']);

				Message::set(Message::SUCCESS, __('You are successfully signed up.'));
				$this->request->redirect(Route::get('profile')->uri(array('username' => $post['username'])));
			}
		}
		catch (ORM_Validation_Exception $ex) {
			$errors = $ex->errors('models');
		}
	}

	/**
	 * Validates new user, adds 'user' role to the newly signed up user
	 * 
	 * @uses Model_User::confirm_signup()
	 */
	public function action_confirm_signup()
	{
		$id = (int) Arr::get($_GET, 'id', 0);
		$auth_token = (string) Arr::get($_GET, 'auth_token', '');

		// Check if someone else is already logged in, if so log him out
		$this->handle_active_logins($id);

		if ($this->user->confirm_signup($id, $auth_token))
		{
			Message::set(Message::SUCCESS, __('Your account has been confirmed.'));
				
			// Automatically log the user in
			$this->auth->force_login($this->user->username);
				
			$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));
		}

		Message::set(Message::ERROR, __('Confirmation link is expired or not valid, sorry.'));
		$this->request->redirect('');
	}

	/* Forgot Password Action Methods */

	/**
	 * Forgot Password action.
	 * User can enter his/her email and request a reset pass mail.
	 *
	 * @uses Auth::logged_in()
	 * @uses Model_User::reset_password()
	 */
	public function action_forgot_password()
	{
		if ($this->auth->logged_in())
			$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));

		// Show forgot password form
		$this->template->content = View::factory($this->get_theme_directory() . 'user/forgot_password')
			->set('dir_name', $this->get_active_theme_dir_name())
			->set('token', $this->get_csrf_token())
			->bind('post', $post)
			->bind('errors', $errors);

		// If form is not submitted, return
		if (!$_POST)	return;

		$post = $_POST;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''), '');

		// TODO: use validation object.
		if (!isset($post['email']) || $post['email'] === '')
		{
			$errors = array('email' => 'Email is required.');
			return;
		}

		try
		{
			if ($this->user->reset_password($post, $this->get_theme_directory()))
			{
				Message::set(Message::SUCCESS, __('Please check your email to reset your password.'));
				$this->request->redirect('');
			}
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors = $ex->errors('models');
		}
	}

	/**
	 * Confirms if the reset password link is still valid and not fake.
	 * After confirmation user sees a form to re-create new password.
	 *
	 * @uses Auth::logged_in()
	 * @uses Model_User::confirm_reset_password_link()
	 */
	public function action_confirm_forgot_password()
	{
		$id = (int) Arr::get($_GET, 'id', 0);
		$time = (int) Arr::get($_GET, 'time', 0);
		$auth_token = (string) Arr::get($_GET, 'auth_token', '');

		// Make sure another user is not logged in
		$this->handle_active_logins($id);

		// Validate the confirmation link
		if (!$this->user->confirm_reset_password_link($id, $auth_token, $time))
		{
			Message::set(Message::ERROR, __('Confirmation link is expired or not valid.'));
			$this->request->redirect('');
		}

		// Show forgot password confirmation form
		$this->template->content = View::factory($this->get_theme_directory() . 'user/confirm_forgot_password')
			->set('dir_name', $this->get_active_theme_dir_name())
			->set('token', $this->get_csrf_token())
			->bind('post', $post)
			->bind('errors', $errors);

		// If form is not submitted, return
		if (!$_POST)	return;

		$post = $_POST;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''), '');

		$extra_rules = Validation::factory($post)
			->rule('password', 'not_empty')
			->rule('password_confirm', 'not_empty')
			->rule('password_confirm',  'matches', array(':validation', ':field', 'password'));

		try
		{
			if ($extra_rules->check() === FALSE)
				throw new ORM_Validation_Exception('Model_User', $extra_rules);
				
            if ($this->user->confirm_reset_password_form($post))
            {
              	Message::set(Message::SUCCESS, __('Your password is successfully changed. Please login with your new password.'));
               	$this->request->redirect(Route::get('user')->uri(array('action' => 'login')));
            }
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors = $ex->errors('models');
		}
	}

	/**
	 * Displays a form that user can change his / her password.
	 * After submitting the form, if the data is validated, password change notification is displayed.
	 *
	 * @uses Auth::logged_in()
	 * @uses Model_User::change_password()
	 */
	public function action_change_password()
	{
		// If the user is not logged in, redirect to login page
		if (!$this->auth->logged_in())
			$this->request->redirect(Route::get('user')->uri(array('action' => 'login')));

		// Show change password form
		$this->template->content = View::factory($this->get_theme_directory() . 'user/change_password')
			->set('dir_name', $this->get_active_theme_dir_name())
			->set('token', $this->get_csrf_token())
			->bind('post', $post)
			->bind('errors', $errors);

		// If form is not submitted, return
		if (!$_POST)	return;

		$post = $_POST;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''), '');

		$extra_rules = Validation::factory($post)
			->rule('old_password', 'not_empty')
			->rule('password', 'not_empty')
			->rule('password_confirm', 'not_empty')
			->rule('password_confirm',  'matches', array(':validation', ':field', 'password'));
		
		try
		{
			if ($extra_rules->check() === FALSE)
				throw new ORM_Validation_Exception('Model_User', $extra_rules);
	                 
            if ($this->user->change_password($post))
            {
               	Message::set(Message::SUCCESS, __('Your password has been changed.'));
               	$this->request->redirect(Route::get('profile')->uri(array('username' => $this->user->username)));
            }
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors = $ex->errors('models');
		}
	}

	/**
	 * Checks if another user is already logged in to the website,
	 * if so, log the user out, and initialize a new Model_User instance
	 *
	 * @param int user id
	 */
	protected function handle_active_logins($id)
	{
		if ($this->auth->logged_in() && $id != $this->user->id)
		{
			$this->auth->logout();
			$this->user = new Model_User;
		}
	}
}
