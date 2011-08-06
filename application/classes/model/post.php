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
	 * @param  int post id
	 * @param  boolean false for cms
	 * @return object instance of Model_Post
	 */
	public static function get($id, $only_moderated = TRUE)
	{
		$post = $only_moderated
			? self::get_moderated_post($id)
			: self::get_post_for_cms($id);
				
		return Model_Post::object_loaded($post, $id) ? $post : NULL;
	}
	
 	/**
	 * Gets moderated post
	 * 
	 * @param  int     post id
	 * @return object  instance of Model_Post
	 */
	public static function get_moderated_post($id)
	{			
		return ORM::factory('post')
			->where('id', '=', $id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)->find();
	}
	
	/**
	 * Gets a post without checking if it is moderated or not
	 * 
	 * @param  int     post id
	 * @return object  instance of Model_Post
	 */	
	public static function get_post_for_cms($id)
	{
		return ORM::factory('post', $id);
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
		
		Kohana_Log::instance()->add(Kohana_Log::ERROR, "Get::Could not fetch the post by ID: $id");
		return FALSE;
	}

	/**
	 * Truncates post content string and returns it
	 * 
	 * @return string
	 */
	public function content_excerpt()
	{
		return nl2br(Text::limit_chars(HTML::chars(strip_tags($this->content))
									, Kohana::config('config.default_post_content_truncate_limit')));
	}

	/**
	 * Returns post content
	 * 
	 * @return string
	 */
	public function get_post_content()
	{
		return $this->content;
	}
	
 	/**
	 * Sanitize html post content
	 * 
	 * @param array posted data
	 */
	public function sanitize_post_content(&$post)
	{
		if (!isset($post['content']))	return;
		
		$allowed_elements = '<p><strong><em><u><h1><h2><h3><h4><h5><h6><img><li><ol><ul><span><div><br><ins><del>
		<address><hr><blockquote>';

		$post['content'] = strip_tags(stripslashes($post['content']), $allowed_elements);
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
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
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
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
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
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
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
		if (! Model_User::check_user_has_write_access()) 	$this->handle_submitted_data_for_guest($post);	
		else	$this->handle_submitted_data_for_user($post);
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
	 * 
	 * @param array posted data
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
				throw new Kohana_Exception("Model_Post::save_post(): Could not save the post with ID: {$this->id}");
			else
				throw new Kohana_Exception('Model_Post::save_post(): Could not save the post');
		}
	}
	
 	/**
	 * Marks a post as anonymous.
	 * Used when a user decided to delete a post
	 *
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	public function mark_post_anonymous()
	{
		$this->latest_activity = time();
		$this->marked_anonymous = 1;
		$this->user_id = 0;
		$this->created_by = 'anonymous';
		$this->notify_email = '0';

		if (!$this->save())
			throw new Kohana_Exception("Model_Post::mark_post_anonymous(): Could not delete {$this->post_type} , ID: {$this->id}");
	}

	/**
	 * Calls Model_Post::handle_user_reputation() to process user reputation
	 *
	 * @param  string reputation type
	 * @param  bool true if rep. point will be decreased according to rep. type
	 * @uses   Model_Post::handle_user_reputation()
	 */
	protected function handle_reputation($reputation_type, $subtract = FALSE)
	{
		$user = Auth::instance()->get_user();
		if (Check::isNullOrFalse($user))
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): Could not get current user.');
			return;
		}
		
		$this->handle_user_reputation($user, $reputation_type, $subtract);
	}
	
	/**
	 * Handles user's reputation points, updates relevant user columns.
	 *
	 * @param  object user
	 * @param  string reputation type
	 * @param  bool true if rep. point will be decreased according to rep. type
	 * @uses   Model_Reputation::create_reputation()
	 * @uses   Model_Reputation::delete_reputation()
	 * @uses   Model_User::update_reputation()
	 */
	protected function handle_user_reputation($user, $reputation_type, $subtract)
	{
		switch ($reputation_type)
		{
			case Model_Reputation::QUESTION_ADD:
					$user->question_count += ($subtract) ? -1 : 1;
				break;
			case Model_Reputation::ANSWER_ADD:
					$user->answer_count += ($subtract) ? -1 : 1;
				break;
			case Model_Reputation::COMMENT_ADD:
					$user->comment_count += ($subtract) ? -1 : 1;
				break;
		}

		// Insert a new reputation entry
		try {
			if ($subtract)
				ORM::factory('reputation')->delete_reputation($user->id, $this->id, $reputation_type);
			else
				$reputation = Model_Reputation::create_reputation($user->id, $this->id, $reputation_type);

			/* Update user reputation point
			   By default current user's point is changed, but in some voting actions, 
			   owner user's reputation is also changed */
			switch ($reputation_type)
			{
				case Model_Reputation::OWN_ACCEPTED_ANSWER:
				case Model_Reputation::OWN_ANSWER_VOTED_DOWN:
				case Model_Reputation::OWN_ANSWER_VOTED_UP:
				case Model_Reputation::OWN_QUESTION_VOTED_DOWN:
				case Model_Reputation::OWN_QUESTION_VOTED_UP:

					if ($this->user_id < 1)
						break;

					if (($owner_user = Model_User::get($this->user_id)) === NULL)
					{
						Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): ' .
				 			'Owner user (userid: ' . $this->user_id . ') could not be loaded to change reputation.');
						break;
					}

					$owner_user->update_reputation($reputation_type, $subtract);
					
					BadgeService::instance()->handle_badges($owner_user, $reputation_type, $subtract);
					break;
				default:
					$user->update_reputation($reputation_type, $subtract);
			
					BadgeService::instance()->handle_badges($user, $reputation_type, $subtract);
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
		if (! Model_User::check_user_has_write_access($user))
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
			throw new Kohana_Exception('Model_Post::check_user_previous_votes(): ' . $ex->getMessage());
		}
		
		return -1;
	}
	
	/**
	 * Checks if the same vote has already been voted by the same user
	 * 
	 * @param  int    vote_type
	 * @param  object Model_Reputation instance
	 * @param  object Model_Reputation instance
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
	 * @param  string post type to be added
	 * @param  bool default increases parent question's relevant field
	 * @throws ORM_Validation_Exception
	 */
	protected function update_parent_stats($post_type, $increase = TRUE)
	{
		if (($parent_post = Model_Post::get($this->parent_post_id, FALSE)) === NULL)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::update_parent_stats(): Could not get parent post. ID: ' . $this->id . ', parent ID: ' . $this->parent_post_id);
			return;
		}
		
		if ($post_type === Helper_PostType::ANSWER)	$count_column = 'answer_count';
		else $count_column = 'comment_count';

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
		if (($owner_user = Model_User::get($this->user_id)) === NULL)
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
	 * Checks if user can modify or not
	 * 
	 * @param  object to be filled by Model_User object if valid
	 * @return boolean
	 */
	protected function user_can_modify(&$user)
	{
		if (! Model_User::check_user_has_write_access($user))
			return FALSE;
		
		return ($this->user_id === $user->id || 
			$user->has('roles', ORM::factory('role', array('name' => 'admin'))));
	}
	
	/**
	 * Checks if question is updated by an admin. If so, mark updated_by field
	 * 
	 * @param object Model_User instance
	 */
	protected function check_updated_by_admin($user)
	{
		if ($user->id !== $this->user_id)
			$this->updated_by = $user->username;
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
	
	/* CMS METHODS */
	
	/**
	 * Returns total count of the posts for cms pages
	 *
	 * @param  string post type
	 * @param  string post moderation type
	 * @return int
	 */
	public static function cms_count_posts($post_type, $moderation_type)
	{
		return ORM::factory('post')->where('post_moderation', '=', $moderation_type)
			->and_where('post_type', '=', $post_type)->count_all();
	}
		
	/**
	 * Returns questions by page size and offset for cms pages
	 * 
	 * @param  int    page size
	 * @param  int    offset
	 * @param  string post type
	 * @param  string post moderation type
	 * @return array  Model_Question objects
	 */
	public static function cms_get_posts($page_size, $offset, $type, $moderation_type)
	{
		return ORM::factory('post')->where('post_moderation', '=', $moderation_type)
			->and_where('post_type', '=', $type)
			->limit($page_size)->offset($offset)
			->order_by('answer_count', 'desc')->order_by('latest_activity', 'desc')->find_all();
	}
	
	/**
	 * Moderates a post
	 * 
	 * @param  string moderation type
	 * @return int
	 */
	public function cms_moderate($moderation_type)
	{
		if (!$this->cms_valid_moderation_type($moderation_type))	return -1;
		
		if ($this->post_moderation === $moderation_type)	return 1;
		
		$old_moderation_type = $this->post_moderation;	
		$this->post_moderation = $moderation_type;
		
		try {
			if ($this->save())
			{
				Kohana_Log::instance()->add(Kohana_Log::INFO, sprintf('CMS_MODERATE POST: post_id: %d was: %s ' 
					. ', made: %s', $this->id, $old_moderation_type, $moderation_type));
					
				$this->delete_tags_if_question($moderation_type);
				
				$this->cms_process_post_moderation_effects($old_moderation_type, $moderation_type, TRUE);
				return 1;
			}
			else	return 0;
		}
		catch (Exception $ex) {
			throw new Kohana_Exception('Model_Post::cms_moderate(): ' . $ex->getMessage());
		}
	}
	
	/**
	 * Checks if posted moderation type value is valid
	 * 
	 * @param  string moderation type
	 * @return boolean
	 */
	private function cms_valid_moderation_type($moderation_type)
	{
		return $moderation_type === Helper_PostModeration::APPROVED 
			|| $moderation_type === Helper_PostModeration::DISAPPROVED
			|| $moderation_type === Helper_PostModeration::DELETED
			|| $moderation_type === Helper_PostModeration::IN_REVIEW
			|| $moderation_type === Helper_PostModeration::NORMAL;
	}
	
	/**
	 * If post type is question, then delete all tags of the question
	 * 
	 * @param string moderation type
	 */
	public function delete_tags_if_question($moderation_type)
	{
		if ($this->post_type !== Helper_PostType::QUESTION 
			|| ($moderation_type !== Helper_PostModeration::DELETED
				&& $moderation_type !== Helper_PostModeration::DISAPPROVED))	return;
				
		$question = Model_Question::get($this->id, FALSE);
		$question->cms_delete_all_tags();
	}
	
	/**
	 * After a post has been marked, user's reputation, parent post's (if any) question/answer count
	 * and user's question/answer count etc. should be changed 
	 * 
	 * @param string post's old moderation type
	 * @param string post's new moderation type
	 */
	public function cms_process_post_moderation_effects($old_moderation_type, $new_moderation_type, $update_parent_stats)
	{
		if ($new_moderation_type === Helper_PostModeration::IN_REVIEW 
			|| $this->user_id === NULL || $this->user_id < 1
			|| ($user = Model_User::get($this->user_id)) === NULL
			)
			return;
			
		if ($update_parent_stats) 
			$update_parent_stats = ($this->post_type !== Helper_PostType::QUESTION);
			
		switch ($this->post_type)
		{
			case Helper_PostType::QUESTION:
				$reputation_type = Helper_ReputationType::QUESTION_ADD;
				break;
			case Helper_PostType::ANSWER:
				$reputation_type = Helper_ReputationType::ANSWER_ADD;
				break;
			case Helper_PostType::COMMENT:
				$reputation_type = Helper_ReputationType::COMMENT_ADD;
				break;
		}
			
		switch ($new_moderation_type)
		{
			case Helper_PostModeration::APPROVED:
			case Helper_PostModeration::NORMAL:
				
				if ($old_moderation_type === Helper_PostModeration::APPROVED || 
					$old_moderation_type === Helper_PostModeration::NORMAL)	
					return;
				
				$this->handle_user_reputation($user, $reputation_type, FALSE);
				
				if ($update_parent_stats)
					$this->update_parent_stats($this->post_type, TRUE);
				break;

			case Helper_PostModeration::DELETED:
			case Helper_PostModeration::DISAPPROVED:
				
				if ($old_moderation_type === Helper_PostModeration::DELETED || 
					$old_moderation_type === Helper_PostModeration::DISAPPROVED)	
					return;
					
				$this->handle_user_reputation($user, $reputation_type, TRUE);
		
				if ($update_parent_stats)
					$this->update_parent_stats($this->post_type, FALSE);
				break;
		}
	}
	/* CMS METHODS */
}