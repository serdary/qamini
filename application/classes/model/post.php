<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Post Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
 class Model_Post extends ORM {

	// Auto-update column for creation and update
	protected $_created_column = array('column' => 'created_at', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated_at', 'format' => TRUE);

	protected $_belongs_to = array('user' => array());

	protected $_has_many = array('tags' => array('model' => 'tag', 'through' => 'post_tag'));
	
	protected $_table_name = 'posts';

	const QUESTION = 'question';
	const ANSWER = 'answer';
	const COMMENT = 'comment';
	
	/**
	 * Validation rules for post object
	 * 
	 * @see Kohana_ORM::rules()
	 */
	public function rules()
	{
		return array(
			'content' => array(
				array('not_empty'),
				array('min_length', array(':value', 20)),
			),
		);
	}
	
	/**
	 * Filters for post object
	 * 
	 * @see Kohana_ORM::filters()
	 */
	public function filters()
	{
	    return array(
	        'content' => array(
	            array('trim'),
	        ),
	    );
	}

	/**
	 * Returns post by id
	 *
	 * @param int post id
	 * @return object
	 */
	public static function get($id)
	{
		$post = ORM::factory('post')
			->where('id', '=', $id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)->find();
		
		return Model_Post::object_loaded($post, $id) ? $post : NULL;
	}
	
 	/**
	 * Checks if an object is loaded
	 * 
	 * @param object obj
	 * @param int
	 */
	protected static function object_loaded($obj, $id)
	{
		if ($obj->loaded())	return TRUE;
		
		Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Get::Could not fetch the post by ID: ' . $id);
		return FALSE;
	}

	/**
	 * Truncates post content string and returns it
	 * 
	 * @return string
	 */
	public function content_excerpt()
	{
		return nl2br(Text::limit_chars(HTML::chars($this->content)
									, Kohana::config('config.default_post_content_truncate_limit')));
	}

	/**
	 * Change new lines to breaks and returns post content
	 * 
	 * @return string
	 */
	public function get_post_content()
	{
		return nl2br(HTML::chars($this->content));
	}

	/**
	 * Gets a post's owner name and user id if post owner is a registered user
	 *
	 * @return array 'created_by' => string, owner name, 'id' => user id if owner registered is a user
	 */
	public function get_post_owner_info()
	{
		if ($this->is_post_created_by_user())	return $this->create_user_post_owner_info();
		
		if ($this->is_post_created_by_guest())	return $this->create_guest_post_owner_info();

		return array('created_by' => __('anonymous'), 'id' => NULL);
	}
	
	/**
	 * Checks if the post is created by a guest
	 *
	 * @return boolean
	 */
	private function is_post_created_by_guest()
	{
		return (isset($this->created_by) && $this->created_by !== '');
	}
	
	/**
	 * Checks if the post is created by a user
	 *
	 * @return boolean
	 */
	private function is_post_created_by_user()
	{
		return !isset($this->created_by) && $this->user->loaded() && $this->user_id > 0;
	}
	
	/**
	 * Returns post's quest owner information
	 *
	 * @return array
	 */
	private function create_guest_post_owner_info()
	{
		return array('created_by' => $this->created_by, 'id' => NULL);
	}
	
	/**
	 * Returns post's user owner information
	 *
	 * @return array
	 */
	private function create_user_post_owner_info()
	{
		return array('created_by' => $this->user->username, 'id' => $this->user->id);
	}

	/**
	 * Returns total count of the 'valid' posts
	 *
	 * @param  string post type
	 * @return int
	 */
	public function count_all_posts($post_type)
	{
		return $this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', $post_type)->count_all();
	}

	/**
	 * Returns total answered count of the 'valid' posts
	 *
	 * @param  string post type
	 * @return int
	 */
	public function count_answered_posts($post_type)
	{
		return $this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', $post_type)
			->and_where('answer_count', '>', 0)->count_all();
	}

	/**
	 * Returns total unanswered count of the 'valid' posts
	 *
	 * @param  string post type
	 * @return int
	 */
	public function count_unanswered_posts($post_type)
	{
		return $this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', $post_type)
			->and_where('answer_count', '=', 0)->count_all();
	}
	
	/**
	 * Increase upvote / downvote count of the post and handles reputation
	 *
	 * @param  string reputation type
	 * @param  string reputation type for owner
	 * @uses   Model_Post::handle_reputation()
	 * @throws ORM_Validation_Exception
	 * @return int 1 => Success
	 */
	public function vote_post($reputation_type, $reputation_type_owner)
	{
		$this->save();

		$this->handle_reputation($reputation_type);
		$this->handle_reputation($reputation_type_owner);

		return 1;
	}

	/**
	 * Used format a field of Model_Post object.
	 * Currently values are formatted if they are above 1000, no matter what type stat is.
	 *
	 * @param  string stat type
	 * @return string
	 */
	public function format_stat($stat_type = Helper_StatType::VIEW_COUNT)
	{
		switch ($stat_type)
		{
			case Helper_StatType::ANSWER_COUNT:
				return $this->create_shortened_stat_number($this->answer_count);
			case Helper_StatType::COMMENT_COUNT:
				return $this->create_shortened_stat_number($this->comment_count);
			case Helper_StatType::OVERALL_VOTE:
				return $this->create_shortened_stat_number($this->up_votes - $this->down_votes);
			default:
				return $this->create_shortened_stat_number($this->view_count);
		}
	}
	
	/**
	 * Returns shortened given stat value
	 * 
	 * @param int value
	 * @return string
	 */
	private function create_shortened_stat_number($value)
	{
		return (floor($value / 1000) > 0) ? floor($value / 1000) . ' K' : $value;
	}

	/**
	 * Returns the relative creation time of the post. (According to now)
	 * 
	 * @return string
	 */
	public function get_relative_creation_time()
	{
		$created_time_obj = new DateTimeQamini($this->created_at);
		
		echo $created_time_obj->get_relative_diff(time());
	}

	/**
	 * Handles post  requests (Add / Edit) for Model_Post objects
	 *
	 * @param  array posted data
	 */
	public function handle_submitted_post_data(&$post)
	{
		if (Auth::instance()->get_user() !== FALSE)	$this->handle_submitted_data_for_user($post);
		else	$this->handle_submitted_data_for_guest($post);
	}
	
	/**
	 * Handles post  requests for logged in users
	 *
	 * @param  array posted data
	 */
	private function handle_submitted_data_for_user(&$post)
	{
		$user = Auth::instance()->get_user();
		
		// If user is logged in, 1 => user wants to be notified about this post, 0 => no email
		$post['notify_user'] = (isset($post['notify_user']) && $post['notify_user'] === 'on') ? '1' : '0';

		// Add user id to the post array
		$post += array('user_id' => $user->id);
	}
	
	/**
	 * Handles post  requests for guests
	 *
	 * @param  array posted data
	 */
	private function handle_submitted_data_for_guest(&$post)
	{
		// TODO: !
		$this->notify_email = isset($post['user_notification_email'])
			? $post['user_notification_email']
			: '';

		$post['notify_user'] = (isset($post['notify_user']) && $post['notify_user'] === 'on')
			? $this->notify_email
			: '0';
	}

	/**
	 * Used to fill a new post object's fields. Calls save_post to insert the post to the DB.
	 *
	 * @param  array posted data
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Post::handle_reputation()
	 * @uses   Model_Post::send_post_update_notification()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	protected function create_post($post)
	{
		$this->add_user_id_to_post($post);
		
		$this->updated_at = time();

		$this->add_created_by_if_visitor($post);
			
		$this->save_post($post);
		
		if ($this->check_post_has_parent())	
			$this->send_post_update_notification();
	}
	
	/**
	 * Adds user id if post is submitted by a user
	 */
	private function add_user_id_to_post($post)
	{
		if (Arr::get($post, 'user_id', 0) > 0)
			$this->user_id = $post['user_id'];
	}
	
	/**
	 * Adds created by string if post is submitted by a visitor
	 */
	private function add_created_by_if_visitor($post)
	{
		if (Arr::get($post, 'created_by', '') != '')
			$this->created_by = $post['created_by'];
	}
	
	/**
	 * Checks if the current post has a parent or not
	 */
	private function check_post_has_parent()
	{
		return $this->parent_post_id !== NULL && $this->parent_post_id > 0;
	}

	/**
	 * Saves the current post. Used to insert or update a post.
	 *
	 * @param  array posted data
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	protected function save_post($post)
	{
		$this->title = (isset($post['title'])) ? trim($post['title']) : NULL;
		$this->slug = (isset($post['title'])) ? URL::title($this->title) : NULL;
		$this->content = $post['content'];
		$this->latest_activity = time();

		if (Arr::get($post, 'notify_user', '') !== '')
			$this->notify_email = $post['notify_user'];

		if (!$this->save())
		{
			if ($this !== NULL && $this->id > 0)
				throw new Kohana_Exception('Model_Post::save_post(): Could not save the post with ID: ' . $this->id);
			else
				throw new Kohana_Exception('Model_Post::save_post(): Could not save the post');
		}
	}
	
 	/**
	 * Marks a post as anonymous.
	 * Used when a user decided to delete a question or an answer
	 *
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	protected function mark_post_anonymous()
	{
		$this->latest_activity = time();
		$this->post_moderation = Helper_PostModeration::MARKED_ANONYMOUS;
		$this->user_id = 0;
		$this->created_by = 'anonymous';
		$this->notify_email = '0';

		if (!$this->save())
			throw new Kohana_Exception('Model_Post::mark_post_anonymous(): Could not delete '
				. $this->post_type . ' with ID: ' . $this->id);
	}

	/**
	 * Handles reputation points, updates appropriate user columns.
	 *
	 * @param  string reputation type
	 * @param  bool true if rep. point will be decreased according to rep. type
	 * @uses   Model_Reputation::create_reputation()
	 * @uses   Model_Reputation::delete_reputation()
	 * @uses   Model_User::update_reputation()
	 */
	protected function handle_reputation($reputation_type, $subtract = FALSE)
	{
		if (($user = Auth::instance()->get_user()) === FALSE)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): Could not get current user.');
			return;
		}

		switch ($reputation_type)
		{
			case Model_Reputation::QUESTION_ADD:
					$user->question_count += ($subtract) ? -1 : 1;
				break;
			case Model_Reputation::ANSWER_ADD:
					$user->answer_count += ($subtract) ? -1 : 1;
				break;
		}

		// Insert a new reputation entry
		try {
			if ($subtract)
				ORM::factory('reputation')->delete_reputation($user->id, $this->id, $reputation_type);
			else
				$reputation = Model_Reputation::create_reputation($user->id, $this->id, $reputation_type);

			// Update user reputation point
			// By default current user's point is changed, but in some voting actions, owner user's reputation is also changed
			switch ($reputation_type)
			{
				case Model_Reputation::OWN_ACCEPTED_ANSWER:
				case Model_Reputation::OWN_ANSWER_VOTED_DOWN:
				case Model_Reputation::OWN_ANSWER_VOTED_UP:
				case Model_Reputation::OWN_QUESTION_VOTED_DOWN:
				case Model_Reputation::OWN_QUESTION_VOTED_UP:

					if ($this->user_id < 1)
						break;

					if (!($owner_user = ORM::factory('user')->get_user_by_id($this->user_id)))
					{
						Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): ' .
				 			'Owner user (userid: ' . $this->user_id . ') could not be loaded to change reputation.');
						break;
					}

					$owner_user->update_reputation($reputation_type, $subtract);
					break;
				default:
					$user->update_reputation($reputation_type, $subtract);
					break;
			}
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): ' . $ex->getMessage());
		}
	}
	
 	/**
	 * Checks if the same user has already voted for this post.
	 * If the prev vote is the opposite of the current one, deletes it and update user's reputation
	 * If the same vote is used before, do nothing, inform user.
	 *
	 * @param  int type of the vote. (up or down)
	 * @param  int reputation type for up vote
	 * @param  int reputation type for down vote
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception
	 * @return int 1 => not voted before, -1 => opposite vote is used before, -2 => the same vote is used before
	 */
	protected function check_user_previous_votes($vote_type, $reputation_type_up, $reputation_type_down)
	{
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::check_user_previous_votes(): Could not get current user');

		$previous_vote_up = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
			$this->id, $reputation_type_up);

		$previous_vote_down = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
			$this->id, $reputation_type_down);

		// User has not voted this post before. (up or down)
		if (!Model_Reputation::any_object_loaded(array($previous_vote_up, $previous_vote_down)))
			return 1;

		if ($this->same_post_already_voted($vote_type, $previous_vote_up, $previous_vote_down))
			return -2;

		// User has voted this post before but different type.
		// The opposite vote is given by the same user, delete it, update user's reputation
		if ($vote_type === 0)
		{
			$this->handle_reputation($reputation_type_up, TRUE);

			$this->handle_reputation(Model_Reputation::get_owner_type($reputation_type_up), TRUE);

			$this->up_votes--;
		}
		else
		{
			$this->handle_reputation($reputation_type_down, TRUE);

			$this->handle_reputation(Model_Reputation::get_owner_type($reputation_type_down), TRUE);
				
			$this->down_votes--;
		}

		try {
			$this->save();
		}
		catch (Exception $ex) {
			throw new Kohana_Exception('Model_Answer::check_user_previous_votes(): ' . $ex->getMessage());
		}
		
		return -1;
	}
	
	/**
	 * Checks if the same vote has already been voted by the same user
	 * 
	 * @param  int vote_type
	 * @param  Model_Reputation object
	 * @param  Model_Reputation object
	 * @return boolean
	 */
	private function same_post_already_voted($vote_type, $previous_vote_up, $previous_vote_down)
	{
		return ($vote_type === 0 && $previous_vote_down->loaded()) 
			|| ($vote_type === 1 && $previous_vote_up->loaded());
	}

	/**
	 * Increases or decreases parent post's relevant (answer or comment count) field
	 *
	 * @param  string count column name of Model_Post object
	 * @param  bool default increases parent question's relevant field
	 * @throws ORM_Validation_Exception
	 */
	protected function update_parent_stats($count_column, $increase = TRUE)
	{
		if (($parent_post = Model_Post::get($this->parent_post_id)) === NULL)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::update_parent_stats(): Could not get parent post. ID: ' . $this->id . ', parent ID: ' . $this->parent_post_id);
			return;
		}

		$parent_post->$count_column += ($increase) ? 1 : -1;

		if (!$parent_post->save())
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::update_parent_stats(): Could not save parent post. ID: ' . $parent_post->id);
	}

	/***** PRIVATE METHODS *****/
	
	/**
	 * Sends notification email if the parent post owner wants 
	 * to be notified about changes on his/her post.
	 */
	private function send_post_update_notification()
	{
		if (($parent_post = Model_Post::get($this->parent_post_id)) === NULL)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR
			, 'Model_Post::send_post_update_notification(): Could not get parent post. ID: ' 
			. $this->id . ', parent ID: ' . $this->parent_post_id);
			
			return;
		}

		if (!$parent_post->wants_notification_mails())	return;

		// If parent post and this post have same owner, don't send any email
		if ($parent_post->user_id === $this->user_id)	return;
		
		$email_fields = $parent_post->get_email_fields();

		// If the parent post is an answer, we need to get its parent question for id and slug
		if ($parent_post->parent_post_id !== NULL)
		{
			if (($post = Model_Question::get($parent_post->parent_post_id)) === NULL)
			{
				Kohana_Log::instance()->add(Kohana_Log::ERROR
				, 'Model_Post::send_post_update_notification(): Could not get parent post. ID: '
				 . $parent_post->id . ', parent ID: ' . $parent_post->parent_post_id);
				 
				return;
			}
		}
		else
		{
			$post = $parent_post;
		}
		
		$link = URL::site(Route::get('question')->uri(
		array('action'=>'detail', 'id' => $post->id, 'slug' => $post->slug)), 'http');
		
		$mailer = new QaminiMailer($email_fields['email_address'], $email_fields['created_by']
		, Kohana::config('config.website_name'), Kohana::config('config.website_name')
		, 'post_update_notification_email', array('url' => $link, 'created_by' => $email_fields['created_by']));
		
		$mailer->send();
	}
	
	/**
	 * Checks if a post owner wants to be notified or not
	 * 
	 * @return boolean
	 */
	private function wants_notification_mails()
	{
		return ($this->notify_email !== NULL && $this->notify_email !== '0');
	}
	
	/**
	 * Returns email information to send a notification email
	 * 
	 * @return mixed
	 */	
	private function get_email_fields()
	{
		if ($this->post_send_by_registered_user())	return $this->set_email_fields_from_user();
		
		return $this->set_email_fields_from_guest();
	}
	
	/**
	 * checks if post owner a registered user or not
	 * 
	 * @return boolean
	 */
	private function post_send_by_registered_user()
	{
		return $this->notify_email === '1';
	}
	
	/**
	 * Sets address and name of the email from user info
	 * 
	 * @return mixed
	 */
	private function set_email_fields_from_user()
	{			
		if (($owner_info = $this->get_parent_post_owner()) === NULL)	return NULL;
		
		return array('email_address' => $owner_info->email, 'created_by' => $owner_info->username);
	}
	
	/**
	 * Sets address and name of the email from guest info
	 * 
	 * @return mixed
	 */
	private function set_email_fields_from_guest()
	{
		$created_by = ($this->created_by === NULL) ? '' : $this->created_by;
		
		return array('email_address' => $this->notify_email, 'created_by' => $created_by);
	}
	
	/**
	 * Returns a post's owner, if not found, logs and returns null
	 * 
	 * @return mixed
	 */
	private function get_parent_post_owner()
	{
		if (!($owner_user = ORM::factory('user')->get_user_by_id($this->user_id)))
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR
			, 'Model_Post::get_parent_post_owner(): Could not get parent post user. ID: ' 
			. $this->id . ', user ID: ' . $this->user_id);
			
			return NULL;
		}
		
		return $owner_user;
	}
	
 	/**
	 * Checks if the post is deleted
	 * 
	 * @return boolean
	 */
	protected function is_deleted()
	{
		return $this->post_moderation === Helper_PostModeration::DELETED;
	}
	
 	/**
	 * Returns all tags of the posts
	 * 
	 * @return array
	 */
	public function get_tags()
	{
		return ORM::factory('post', $this->id)->tags->find_all();
	}
}