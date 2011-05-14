<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini User Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Model_User extends Model_Auth_User {

	protected $_has_many = array
	(
		'user_tokens'	=> array('model' => 'user_token'),
		'roles'       	=> array('model' => 'role', 'through' => 'roles_users'),
		'posts' 		=> array(),
	);

	/**
	 * User Model Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function rules()
	{
		return array(
			'username' => array(
				array('not_empty'),
				array('min_length', array(':value', 1)),
				array('max_length', array(':value', 32)),
				array('regex', array(':value', '/^[-\pL\pN_.]++$/uD')),
				array(array($this, 'username_available'), array(':validation', ':field')),
			),
			'password' => array(
				array('not_empty'),
			),
			'email' => array(
				array('not_empty'),
				array('min_length', array(':value', 4)),
				array('max_length', array(':value', 127)),
				array('email'),
				array(array($this, 'email_available'), array(':validation', ':field')),
			),
		);
	}
	
	public function filters()
	{
	    return array(
	        'username' => array(
	            array('trim'),
	        ),
	        'email' => array(
	            array('trim'),
	        ),
			'password' => array(
				array(array(Auth::instance(), 'hash'))
			)
	    );
	}

	/**
	 * Returns user by user id
	 *
	 * @param  int    user id
	 * @return object
	 */
	public function get_user_by_id($id)
	{
		$user = $this->where('id', '=', $id)->find();

		return ($user->loaded() === TRUE) ? $user : NULL;
	}

	/**
	 * Log user in if the data is validated
	 *
	 * @param  array   data
	 * @param  boolean remember me functionality
	 * @return boolean
	 */
	public function login(array &$data, $remember = FALSE)
	{
		$data = Validation::factory($data);

		if (!$data->check())	return FALSE;

		// Try to load the user
		$this->where('username', '=', $data['username'])->find();

		// Try to log user in
		if ($this->loaded() AND Auth::instance()->login($this, $data['password'], $remember))
			return TRUE;

		return FALSE;
	}
	
	/**
	 * Updates user's columns after login
	 */
	public function complete_login()
	{
		if (!$this->_loaded)	return;

		$this->logins = new Database_Expression('logins + 1');
		$this->last_login = $this->latest_activity = time();
		$this->last_ip = Request::$client_ip;

		$this->save();
	}

	/**
	 * Signs user up
	 * After validating user submitted data, new user is created with "login" role
	 *
	 * @param  array   data
	 * @param  string  directory of the current theme
	 * @throws ORM_Validation_Exception
	 * @return boolean
	 */
	public function signup(array &$data, $theme_dir)
	{
		$this->values($data);

		$this->save();

		$this->add('roles', ORM::factory('role', array('name' => 'login')));

		$this->send_signup_mail();

		return TRUE;
	}
	
	/**
	 * Sends signed up mail to the user
	 */
	private function send_signup_mail()
	{
		$link = URL::site(Route::get('user_ops')->uri(array('action' => 'confirm_signup'))
												. '?id=' . $this->id . '&auth_token='
												. Auth::instance()->hash($this->email), 'http');
											
		$mailer = new QaminiMailer($this->email, $this->username
			, Kohana::config('config.website_name') . __(' - Signup') 
			, Kohana::config('config.website_name') . __(' Website'), 'confirm_signup'
			, array('url' => $link, 'username' => $this->username));
			
		$mailer->send();
	}

	/**
	 * Confirms user's registration by adding 'user' role to the user
	 *
	 * @param   integer  user id
	 * @param   string   confirmation token
	 * @return  boolean
	 */
	public function confirm_signup($id, $token)
	{
		if ($id < 0 || empty($token) || ($this->loaded() && $this->id != $id))	return FALSE;

		if (!$this->loaded())	$this->where('id', '=', $id)->find();

		if (!$this->loaded())	return FALSE;

		if (!$this->token_is_valid($token))	return FALSE;

		// If user is not already confirmed, add user role
		if (!$this->has('roles', ORM::factory('role', array('name' => 'user'))))
		{
			$this->add('roles', ORM::factory('role', array('name' => 'user')));
		}

		return TRUE;
	}
	
	/**
	 * Checks if token is valid
	 * 
	 * @param boolean
	 */
	private function token_is_valid($token)
	{
		return $token === Auth::instance()->hash($this->email);
	}

	/**
	 * Sends an email contains a link to new password form.
	 * A timestamp and validation code is added to the link to validate
	 *
	 * @param  array    posted data
	 * @param  string   directory of the current theme
	 * @throws ORM_Validation_Exception
	 * @return boolean
	 */
	public function reset_password(array &$data, $theme_dir)
	{
		$email_rules = Validation::factory($data)
			->rule('email', 'email')
			->rule('email', array($this, 'is_email_registered'));

		if (!$email_rules->check())
			throw new ORM_Validation_Exception('user', $email_rules);

		// Load user data
		$this->where('email', '=', $data['email'])->find();
		
		$this->send_reset_password_mail();
			
		return TRUE;
	}
	
	/**
	 * Sends user a reseet password instructions mail
	 */
	private function send_reset_password_mail()
	{
		$time = time();
		
		$uri = Route::get('user_ops')->uri(array('action' => 'confirm_forgot_password'))
				. '?id=' . $this->id . '&auth_token='
				. Auth::instance()->hash(sprintf('%s_%s_%d', $this->email, $this->password, $time))
				. '&time=' . $time;
				
		$link = URL::site($uri, 'http');
			
		$mailer = new QaminiMailer($this->email, $this->username
			, Kohana::config('config.website_name') . __(' - Reset Password') 
			, Kohana::config('config.website_name') . __(' Website'), 'confirm_reset_password'
			, array('url' => $link, 'username' => $this->username));
			
		$mailer->send();
	}

	/**
	 * Validates the confirmation link for a password reset.
	 *
	 * @param  integer  user id
	 * @param  string   confirmation token
	 * @param  integer  timestamp
	 * @return boolean
	 */
	public function confirm_reset_password_link($id, $auth_token, $time)
	{
		if ($id === 0 || $auth_token === '' || $time === 0)
			return FALSE;

		if ($this->confirmation_link_expired($time))	return FALSE;

		if (!$this->loaded())	$this->where('id', '=', $id)->find();

		if (!$this->loaded())	return FALSE;

		// Invalid confirmation token
		if ($auth_token !== Auth::instance()->hash(sprintf('%s_%s_%d', $this->email, $this->password, $time)))
			return FALSE;

		return TRUE;
	}
	
	/**
	 * Checks if the confirmation link is expired or not
	 * 
	 * @param boolean
	 */
	private function confirmation_link_expired($time)
	{
		return ($time + Kohana::config('config.reset_password_expiration_time')) < time();
	}

	/**
	 * Confirms data sent by the user to reset his / her password
	 * If the data has been validated, it is saved
	 *
	 * @param  array values
	 * @throws ORM_Validation_Exception
	 * @return boolean
	 */
	public function confirm_reset_password_form(array $data)
	{
		$data = Validation::factory($data);

		$this->password = $data['password'];

		$this->save();

		if (!$this->has('roles', ORM::factory('role', array('name' => 'user'))))
		{
			$this->add('roles', ORM::factory('role', array('name' => 'user')));
		}

		return TRUE;
	}

	/**
	 * Changes a user's password if data is valid.
	 *
	 * @param  array values
	 * @throws ORM_Validation_Exception
	 * @return boolean
	 */
	public function change_password(array $data)
	{
		$extra_rules = Validation::factory($data)
			->rule('old_password', array($this, 'check_password'));
			
		// Save the new password
		$this->password = $data['password'];

		return $this->save($extra_rules);
	}

	/**
	 * Validates user's old password
	 *
	 * @param  string  field name
	 * @return false on failure
	 */
	public function check_password($old_password)
	{
		if (($user = Auth::instance()->get_user()) && 
			Auth::instance()->password($user->username) === Auth::instance()->hash($old_password))
		{
			return;
		}

		return FALSE;
	}

	/**
	 * Checks DB if the email is registered or not
	 *
	 * @param  string  email
	 * @return void
	 */
	public function is_email_registered($email)
	{
		return $this->unique_key_exists($email, 'email');
	}

	/**
	 * Returns user's posts to display in user profile page
	 *
	 * @param  int    page size
	 * @param  int    offset
	 * @param  string post type
	 * @param  post   status status of the post
	 * @return array  Model_Post objects
	 */
	public function get_user_posts($page_size, $offset, $post_type = Helper_PostType::QUESTION, $post_status = Helper_PostStatus::ALL)
	{
		return $this->posts->where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_type', '=', $post_type)
			->order_by('latest_activity', 'desc')
			->limit($page_size)
			->offset($offset)
			->find_all();
	}

	/**
	 * Returns user's total count of the 'valid' post
	 *
	 * @param  string post type
	 * @param  string status of the posts that will be count
	 * @return int
	 */
	public function count_user_posts($post_type, $status = Helper_PostStatus::ALL)
	{
		switch ($status)
		{
			case Helper_PostStatus::ANSWERED:
				$count = $this->posts->where('post_moderation', '!=', Helper_PostModeration::DELETED)
					->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
					->and_where('answer_count', '>', 0)
					->and_where('post_type', '=', $post_type)
					->count_all();
				break;
			default:
				$count = $this->posts->where('post_moderation', '!=', Helper_PostModeration::DELETED)
					->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
					->and_where('post_type', '=', $post_type)
					->count_all();
				break;
		}

		return $count;
	}

	/**
	 * Updates user's reputation point according to reputation type and subtract
	 *
	 * @param  string reputation type
	 * @param  bool   reputation will be added or subtracted
	 */
	public function update_reputation($reputation_type, $subtract)
	{
		// Calculate user's last reputation value
		$reputation_value = (int) Model_Setting::instance()->get($reputation_type);

		if ($subtract)	$reputation_value *= -1;

		$this->reputation += $reputation_value;
			
		$this->update_last_activity_time();
	}

	/**
	 * Updates and saves a user's lastest activity time
	 */
	public function update_last_activity_time()
	{
		$this->latest_activity = time();

		if (!$this->save())
			Kohana_Log::instance()->add(Kohana_Log::ERROR
				, 'Model_User::update_last_activity_time(): Could not update current user. ID: ' . $this->id);
	}

	/**
	 * Returns user by username
	 *
	 * @param $username The username of the user
	 * @return mixed
	 */
	public static function get_user_by_username($username)
	{
		$user = ORM::factory('user')->where('username', '=', $username)->find();

		return ($user->loaded() === TRUE) ? $user : NULL;
	}
}