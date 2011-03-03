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

	/**
	 * Holds the answers of the post
	 *
	 * @var array
	 */
	public $answers = array();

	/**
	 * Holds the comments of the post
	 *
	 * @var array
	 */
	public $comments = array();

	// Validation rules
	public function rules()
	{
		return array(
			'content' => array(
				array('not_empty'),
				array('min_length', array(':value', 20)),
			),
		);
	}
	
	public function filters()
	{
	    return array(
	        'content' => array(
	            array('trim'),
	        ),
	    );
	}

	/**
	 * Returns post by id and type
	 *
	 * @param  int    post id
	 * @param  string post type, default is 'question'
	 * @return object instance of Model_Post
	 */
	public function get($id, $post_type = Helper_PostType::QUESTION)
	{
		if ($post_type !== Helper_PostType::ALL)
		{
			$post = ORM::factory('post')
				->where('id', '=', $id)
				->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
				->and_where('post_type','=' , $post_type)->find();
		}
		else
		{
			$post = ORM::factory('post')
				->where('id', '=', $id)
				->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)->find();
		}
			
		if (!$post->loaded())
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Get::Could not fetch the post by ID: ' . $id);
			return NULL;
		}

		return $post;
	}

	/**
	 * Returns the truncated post content
	 * 
	 * @return string truncated post content
	 */
	public function content_excerpt()
	{
		return nl2br(Text::limit_chars(HTML::chars($this->content)
									, Kohana::config('config.default_post_content_truncate_limit')));
	}

	/**
	 * Returns post content
	 * 
	 * @return string post content
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
		if (isset($this->created_by))
			return array('created_by' => $this->created_by, 'id' => NULL);

		if (!isset($this->created_by) && $this->user_id > 0)
		{
			if($this->user->loaded() && $this->user->id > 0)
				return array('created_by' => $this->user->username, 'id' => $this->user->id);
				
			return array('created_by' => 'anonymous', 'id' => NULL);
		}

		return array('created_by' => 'anonymous', 'id' => NULL);
	}

	/**
	 * Returns total count of the 'valid' posts
	 *
	 * @param  string post type
	 * @param  string status of the posts that will be count
	 * @return int
	 */
	public function count_posts($post_type, $status = Helper_PostStatus::ALL)
	{
		$this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', $post_type);

		switch ($status)
		{
			case Helper_PostStatus::ANSWERED:
				return $this->and_where('answer_count', '>', 0)->count_all();
			case Helper_PostStatus::UNANSWERED:
				return $this->and_where('answer_count', '=', 0)->count_all();
			default:
				return $this->count_all();
		}
	}

	/* Question Methods */

	/**
	 * Returns questions according to post status, page size and offset
	 *
	 * @param  int    page size
	 * @param  int    offset
	 * @param  string status of the posts that will be count
	 * @return array  Model_Post objects
	 */
	public function get_questions($page_size, $offset, $status = Helper_PostStatus::ALL)
	{
		$this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->limit($page_size)
			->offset($offset);

		switch ($status)
		{
			case Helper_PostStatus::ANSWERED:
				return $this->order_by('answer_count', 'desc')
					->order_by('latest_activity', 'desc')->find_all();
			case Helper_PostStatus::UNANSWERED:
				return $this->and_where('answer_count', '=', 0)
					->order_by('latest_activity', 'desc')->find_all();
			default:
				return $this->order_by('latest_activity', 'desc')->find_all();
		}
	}

	/**
	 * Adds a new question
	 *
	 * @param  array new question data
	 * @uses   Model_Post::create_post()
	 * @uses   Model_Post::add_tags()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return array id => question id, slug => question slug
	 */
	public function add_question($post)
	{
		$question = new Model_Post;
			
		$question->create_post($post, Helper_PostType::QUESTION);

		// Process tags for this question
		try {
			if (isset($post['tags']))
				$question->add_tags($post['tags']);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::add_tags(): ' . $ex->getMessage());
		}

		return array('id' => $question->id, 'slug' => $question->slug);
	}

	/**
	 * Used to edit a question
	 *
	 * @param  array posted question data
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Post::update_tags()
	 * @uses   Model_User::update_user_info()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return array id => question id, slug => question slug
	 */
	public function edit_question($post)
	{
		// Currently only logged in users can edit questions.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::edit_question(): Could not get current user');

		$this->save_post($post);

		// Process tags for this question
		if (isset($post['tags']))
			$this->update_tags($post['tags']);
			
		// Update current user's latest activity time
		$user->update_user_info(array('latest_activity'));
			
		return array('id' => $this->id, 'slug' => $this->slug);
	}

	/**
	 * Used to delete a question
	 *
	 * @uses   Model_Post::mark_post_anonymous()
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	public function delete_question()
	{
		// Currently only logged in users can delete questions.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::delete_question(): Could not get current user');

		// The question marked anonymous (user_id = 0) instead of marked deleted.
		$this->mark_post_anonymous();

		/*
		 * Delete user 'create question' reputation entry.
		 * Decrease user's reputation by 'create_question' reputation point
		 */
		$this->handle_reputation(Helper_ReputationType::QUESTION_ADD, true);
	}

	/**
	 * Returns a question's tags seperated with a comma
	 * Used for editing question action
	 */
	public function generate_tag_list()
	{
		$tag_list = '';
		foreach ($this->tags->find_all() as $tag)
		{
			$tag_list .= $tag->value . ',';
		}

		return ($tag_list === '') ? $tag_list : substr($tag_list, 0, -1);
	}

	/**
	 * Checks new question data
	 *
	 * @param array values to check
	 */
	public function check_question(&$post)
	{
		$post['title'] = trim($post['title']);

		return (isset($post['title']) && $post['title'] !== '' && (strlen($post['title']) > 10));
	}

	/* Answer Methods */

	/**
	 * Gets answers of the question
	 *
	 * @uses Model_Post::create_object()
	 */
	public function get_answers()
	{
		$db_result = ORM::factory('post')
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Helper_PostType::ANSWER)
			->and_where('parent_post_id', '=', $this->id)
			->order_by('latest_activity', 'desc')
			->find_all();

		foreach ($db_result as $answer)
		{
			// If the answer is accepted, add it to the top of the answers array
			if ($answer->post_status === Helper_PostStatus::ACCEPTED)
			{
				array_unshift($this->answers, $this->create_object($answer));
			}
			else
			{
				$this->answers[] = $this->create_object($answer);
			}
		}
	}

	/**
	 * Gets answers and comments of the question
	 *
	 * @uses Model_Post::get_answers()
	 */
	public function get_answers_and_comments()
	{
		$this->get_answers();

		$parent_ids = array();
		$parent_ids[] = $this->id;

		foreach ($this->answers as $answer)
		{
			$parent_ids[] = $answer->id;
		}

		$results = ORM::factory('post')
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Helper_PostType::COMMENT)
			->and_where('parent_post_id', 'IN', DB::Expr(sprintf('(%s)', implode(',', $parent_ids))))
			->order_by('latest_activity', 'desc')
			->find_all();

		foreach ($results as $comment)
		{
			if ($comment->parent_post_id === $this->id)
			{
				$this->comments[] = $comment;
				continue;
			}

			foreach ($this->answers as $answer)
			{
				if ($comment->parent_post_id === $answer->id)
				{
					$answer->comments[] = $comment;
					break;
				}
			}
		}
	}

	/**
	 * Adds a new answer to a question
	 *
	 * @param  array New answer data
	 * @param  int Question id
	 * @uses   Model_Post::create_post()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return bool
	 */
	public function add_answer($post, $question_id)
	{
		$answer = new Model_Post;
			
		$answer->parent_post_id = $question_id;

		$answer->create_post($post, Helper_PostType::ANSWER);
			
		return TRUE;
	}

	/**
	 * Used to edit an answer
	 *
	 * @param  array posted answer data
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Post::get()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return string question slug
	 */
	public function edit_answer($post)
	{
		// Currently only logged in users can edit answer.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::edit_answer(): Could not get current user');

		$this->save_post($post);
			
		// Update current user's latest activity time
		$user->update_user_info(array('latest_activity'));

		if (($parent_post = ORM::factory('post')->get($this->parent_post_id)) === NULL)
			return '';

		return $parent_post->slug;
	}

	/**
	 * Used to delete an answer
	 *
	 * @uses   Model_Post::mark_post_anonymous()
	 * @uses   Model_Post::handle_reputation()
	 * @uses   Model_Post::get()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return string empty or parent post slug
	 */
	public function delete_answer()
	{
		// Currently only logged in users can delete answers.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::delete_answer(): Could not get current user');

		// Mark answer anonymous
		$this->mark_post_anonymous();

		// Delete user 'create answer' reputation entry.
		// Decrease user's reputation by 'create_answer' reputation point
		$this->handle_reputation(Helper_ReputationType::ANSWER_ADD, true);

		// Decrease parent question's answer count
		// $this->update_parent_stats(FALSE);
			
		if (($parent_post = ORM::factory('post')->get($this->parent_post_id)) === NULL)
			return '';

		return $parent_post->slug;
	}

	/* Comment Methods */

	/**
	 * Adds new comment to a post (Question Or Answer)
	 *
	 * @param  array New answer data
	 * @param  int Parent id
	 * @uses   Model_Post::create_post()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return int new comment id on success
	 */
	public function add_comment($post, $parent_id)
	{
		// Currently only logged in users can add comments
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::add_comment(): Could not get current user');

		$comment = new Model_Post;

		$comment->parent_post_id = $parent_id;
			
		// Add user id to the post
		$post['user_id'] = $user->id;

		$comment->create_post($post, Helper_PostType::COMMENT);
			
		return $comment->id;
	}

	/**
	 * Used to delete a comment
	 *
	 * @uses   Model_Post::handle_reputation()
	 * @uses   Model_Post::update_parent_stats()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	public function delete_comment()
	{
		// Currently only logged in users can delete comments.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::delete_comment(): Could not get current user');

		$this->latest_activity = time();
		$this->post_moderation = Helper_PostModeration::DELETED;

		if (!$this->save())
			throw new Kohana_Exception('Model_Post::delete_comment(): Could not delete comment with ID: ' . $this->id);
			
		$this->handle_reputation(Helper_ReputationType::COMMENT_ADD, true);

		// update parent question's comment count
		$this->update_parent_stats(FALSE);
	}

	/* Search Methods */

	/**
	 * Counts posts which are relevant to query string
	 *
	 * @param  string query string
	 * @return int result count
	 */
	public function count_search_results($query)
	{
		if ($query === NULL || $query === '')
			return;
			
		return $this->and_where_open()
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->and_where_close()
			->and_where_open()
			->or_where('title', 'LIKE', '%' . $query . '%')
			->or_where('content', 'LIKE', '%' . $query . '%')
			->and_where_close()
			->count_all();
	}

	/**
	 * Search DB to find relevant posts
	 *
	 * @param  string query string
	 * @param  int    page size
	 * @param  int    offset
	 * @return array  Model_Post objects
	 */
	public function search($query, $page_size, $offset)
	{
		return $this->and_where_open()
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->and_where_close()
			->and_where_open()
			->or_where('title', 'LIKE', '%' . $query . '%')
			->or_where('content', 'LIKE', '%' . $query . '%')
			->and_where_close()
			->order_by('latest_activity', 'desc')
			->limit($page_size)
			->offset($offset)
			->find_all();
	}

	/* Voting Methods */

	/**
	 * Increase upvote / downvote count of the post and handles reputation
	 *
	 * @param  int vote type 0 for down, 1 for up votes
	 * @uses   Model_Post::check_user_previous_votes()
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return int 1 => Success, -2 => User is already voted
	 */
	public function vote_post($vote_type)
	{
		// Check if user voted this post before, if so do the appropriate actions.
		$previous_votes = $this->check_user_previous_votes($vote_type);

		if ($previous_votes !== 1)
			return $previous_votes;

		if ($vote_type === 0)
		{
			$this->down_votes++;

			if ($this->post_type === Helper_PostType::QUESTION)
			{
				$reputation_type = Helper_ReputationType::QUESTION_VOTE_DOWN;
				$reputation_type_owner = Helper_ReputationType::OWN_QUESTION_VOTED_DOWN;
			}
			elseif ($this->post_type === Helper_PostType::ANSWER)
			{
				$reputation_type = Helper_ReputationType::ANSWER_VOTE_DOWN;
				$reputation_type_owner = Helper_ReputationType::OWN_ANSWER_VOTED_DOWN;
			}
		}
		else
		{
			$this->up_votes++;

			if ($this->post_type === Helper_PostType::QUESTION)
			{
				$reputation_type = Helper_ReputationType::QUESTION_VOTE_UP;
				$reputation_type_owner = Helper_ReputationType::OWN_QUESTION_VOTED_UP;
			}
			elseif ($this->post_type === Helper_PostType::ANSWER)
			{
				$reputation_type = Helper_ReputationType::ANSWER_VOTE_UP;
				$reputation_type_owner = Helper_ReputationType::OWN_ANSWER_VOTED_UP;
			}
		}

		if (!isset($reputation_type) || !isset($reputation_type_owner))
			throw new Kohana_Exception('Model_Post::vote_post(): Wrong post type posted!');

		$this->save();

		$this->handle_reputation($reputation_type);
		$this->handle_reputation($reputation_type_owner);

		return 1;
	}

	/**
	 * Accepts / Undo accepts an answer
	 *
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return int 1 => Accepted, 2 => Undo Accept, -1 => Error, -2 => Already Accepted An Answer
	 */
	public function accept_post()
	{
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::accept_post(): Could not get current user');
			
		if ($this->post_moderation === Helper_PostModeration::DELETED)
			return -1;

		// Get the parent question to check its creator is the same as current user
		$question = ORM::factory('post')->get($this->parent_post_id, Helper_PostType::QUESTION);
		if ($question === NULL || $question->user_id !== $user->id)
			return -1;

		// If the post has been accepted before, undo accept
		if ($this->post_status === Helper_PostStatus::ACCEPTED)
		{
			$this->post_status = Helper_PostStatus::PUBLISHED;
			$this->save();

			$this->handle_reputation(Helper_ReputationType::ACCEPTED_ANSWER, TRUE);
			$this->handle_reputation(Helper_ReputationType::OWN_ACCEPTED_ANSWER, TRUE);

			return 2;
		}
		else
		{
			// Check if another answer is already chosen as accepted answer
			if ($this->check_accepted_posts())
			return -2;

			$this->post_status = Helper_PostStatus::ACCEPTED;
			$this->save();

			$this->handle_reputation(Helper_ReputationType::ACCEPTED_ANSWER);
			$this->handle_reputation(Helper_ReputationType::OWN_ACCEPTED_ANSWER);

			return 1;
		}

		return -1;
	}

	/**
	 * Used format a field of Model_Post object.
	 * Currently values are formatted if they are above 1000, no matter what type stat is.
	 *
	 * @param  string stat type
	 * @return string formatted stat number
	 */
	public function format_stat($stat_type = Helper_StatType::VIEW_COUNT)
	{
		// Numbers can be formatted according to their stat type later.

		switch ($stat_type)
		{
			case Helper_StatType::ANSWER_COUNT:
				$value = $this->answer_count;
				break;
			case Helper_StatType::COMMENT_COUNT:
				$value = $this->comment_count;
				break;
			case Helper_StatType::OVERALL_VOTE:
				$value = $this->up_votes - $this->down_votes;
				break;
			default:
				$value = $this->view_count;
				break;
		}

		$tmp = floor($value / 1000);

		return ($tmp > 0) ? $tmp . ' K' : $value;
	}

	/**
	 * Returns the relative creation time of the post. (According to now)
	 * 
	 * @return string relative time
	 */
	public function get_relative_creation_time()
	{
		if (($diff = time() - $this->created_at) <= 0)
			return __('just now!');

		$minute_in_seconds = 60;
		$hour_in_seconds = $minute_in_seconds * 60;
		$day_in_seconds = $hour_in_seconds * 24;
		$month_in_seconds = $day_in_seconds * 30;	// Not so true, need to update
		$year_in_seconds = $month_in_seconds * 12;	// Not so true, need to update

		$year = floor($diff / $year_in_seconds);
		$total_seconds = $year * $year_in_seconds;

		$month = floor(($diff - $total_seconds) / $month_in_seconds);
		$total_seconds += $month * $month_in_seconds;

		$day = floor(($diff - $total_seconds) / $day_in_seconds);
		$total_seconds += $day * $day_in_seconds;

		$hour = floor(($diff - $total_seconds) / $hour_in_seconds);
		$total_seconds += $hour * $hour_in_seconds;

		$minute = floor(($diff - $total_seconds) / $minute_in_seconds);
		$total_seconds += $minute * $minute_in_seconds;

		$second = $diff - $total_seconds;

		$date_tmp2 = '%d %s, %d %s ago';
		$date_tmp1 = '%d %s ago';

		if ($year > 0)
			return ($month > 0) 
				? sprintf($date_tmp2, $month, Inflector::plural('month', $month), $year, Inflector::plural('year', $year))
				: sprintf($date_tmp1, $year, Inflector::plural('year', $year));

		if ($month > 0)
			return ($day > 0) 
				? sprintf($date_tmp2, $day, Inflector::plural('day', $day), $month, Inflector::plural('month', $month))
				: sprintf($date_tmp1, $month, Inflector::plural('month', $month));

		if ($day > 0)
			return ($hour > 0) 
				? sprintf($date_tmp2, $hour, Inflector::plural('hour', $hour), $day, Inflector::plural('day', $day))
				: sprintf($date_tmp1, $day, Inflector::plural('day', $day));

		if ($hour > 0)
			return ($minute > 0) 
				? sprintf($date_tmp2, $minute, Inflector::plural('min', $minute), $hour, Inflector::plural('hour', $hour))
				: sprintf($date_tmp1, $hour, Inflector::plural('hour', $hour));

		if ($minute > 0)
			return ($second > 0) 
				? sprintf($date_tmp2, $second, Inflector::plural('sec', $second), $minute, Inflector::plural('min', $minute))
				: sprintf($date_tmp1, $minute, Inflector::plural('min', $minute));

		return sprintf($date_tmp1, $second, Inflector::plural('sec', $second));
	}

	/**
	 * Increase view count of the current post
	 *
	 * @return true on successful save
	 */
	public function increase_view_count()
	{
		$this->view_count++;
		$this->latest_activity = time();

		return $this->save();
	}

	/**
	 * Handles post  requests (Add / Edit) for Model_Post object.
	 *
	 * @param  array posted array
	 */
	public function handle_post_request(&$post)
	{
		if (($user = Auth::instance()->get_user()) !== FALSE)
		{
			// If user is logged in, 1 => user wants to be notified about this post, 0 => no email
			$post['notify_user'] = (isset($post['notify_user']) && $post['notify_user'] === 'on') ? '1' : '0';

			// Add user id to the post array
			$post += array('user_id' => $user->id);
		}
		else
		{
			$this->notify_email = isset($post['user_notification_email'])
				? $post['user_notification_email']
				: '';

			$post['notify_user'] = (isset($post['notify_user']) && $post['notify_user'] === 'on')
				? $this->notify_email
				: '0';
		}
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Used to fill a new post object's fields. Calls save_post to insert the post to the DB.
	 *
	 * @param  array posted data
	 * @param  string post type
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Post::update_parent_stats()
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	private function create_post($post, $post_type)
	{
		// Add user id if a registered user created the post
		if (isset($post['user_id']) && $post['user_id'] > 0)
		{
			$this->user_id = $post['user_id'];
		}
		$this->post_type = $post_type;
		$this->updated_at = time();

		// Add created by string if it is a visitor
		if (isset($post['created_by']) && $post['created_by'] != '')
		{
			$this->created_by = $post['created_by'];
		}
			
		$this->save_post($post);

		/*
		 * Update parent questions answer / comment count if post_type is not question
		 * If Logged In, Update current user's reputation points and latest activity time
		 */
		try {
			switch ($this->post_type)
			{
				case Helper_PostType::ANSWER:
						$reputation_type = Helper_ReputationType::ANSWER_ADD;
						$this->update_parent_stats();
					break;
				case Helper_PostType::COMMENT:
						$reputation_type = Helper_ReputationType::COMMENT_ADD;
						$this->update_parent_stats();
					break;
				default:
					$reputation_type = Helper_ReputationType::QUESTION_ADD;
					break;
			}
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, $ex->getMessage());
		}

		if (isset($post['user_id']) && $post['user_id'] > 0)
		{
			$this->handle_reputation($reputation_type);
		}
	}

	/**
	 * Saves the current post. Used to insert or update a post.
	 *
	 * @param  array posted data
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	private function save_post($post)
	{
		$this->title = (isset($post['title'])) ? trim($post['title']) : NULL;
		$this->slug = (isset($post['title'])) ? URL::title($this->title) : NULL;
		$this->content = $post['content'];
		$this->latest_activity = time();

		if (isset($post['notify_user']) && $post['notify_user'] !== '')
		{
			$this->notify_email = $post['notify_user'];
		}

		if (!$this->save())
		{
			if ($this !== NULL && $this->id > 0)
				throw new Kohana_Exception('Model_Post::save_post(): Could not save the post with ID: ' . $this->id);
			else
				throw new Kohana_Exception('Model_Post::save_post(): Could not save the post');
		}
	}

	/**
	 * Creates a new Model_Post object from an associative array
	 *
	 * @param  array data
	 * @return object Instance of Model_Post
	 */
	private function create_object($data)
	{
		$new_object = new Model_Post;
		foreach ($data as $key => $value)
		{
			$new_object->$key = $value;
		}

		return $new_object;
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
	private function handle_reputation($reputation_type, $subtract = FALSE)
	{
		if (($user = Auth::instance()->get_user()) === FALSE)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::handle_reputation(): Could not get current user.');
			return;
		}

		switch ($reputation_type)
		{
			case Helper_ReputationType::QUESTION_ADD:
					$user->question_count += ($subtract) ? -1 : 1;
				break;
			case Helper_ReputationType::ANSWER_ADD:
					$user->answer_count += ($subtract) ? -1 : 1;
				break;
		}

		// Insert a new reputation entry
		try {
			if ($subtract)
			{
				ORM::factory('reputation')->delete_reputation($user->id, $this->id, $reputation_type);
			}
			else
			{
				$reputation = new Model_Reputation;
				$reputation->create_reputation($user->id, $this->id, $reputation_type);
			}

			// Update user reputation point
			// By default current user's point is changed, but in some voting actions, owner user's reputation is also changed
			switch ($reputation_type)
			{
				case Helper_ReputationType::OWN_ACCEPTED_ANSWER:
				case Helper_ReputationType::OWN_ANSWER_VOTED_DOWN:
				case Helper_ReputationType::OWN_ANSWER_VOTED_UP:
				case Helper_ReputationType::OWN_QUESTION_VOTED_DOWN:
				case Helper_ReputationType::OWN_QUESTION_VOTED_UP:

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
	 * Marks a post as anonymous.
	 * Used when a user decided to delete a question or an answer
	 *
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	private function mark_post_anonymous()
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
	 * Adds tags for the question.
	 * Searches tag's slug in case that tag is already added. If so increase its used count.
	 * Otherwise, add the tags to the DB. Lastly adds the tag to this question.
	 *
	 * @param  string posted tags
	 * @uses   Model_Post::get_tag_by_slug()
	 */
	private function add_tags($tags)
	{
		if ($tags === '')
			return;

		$tag_array = explode(',', $tags);

		// Loop every entered tag
		foreach ($tag_array as $tag)
		{
			if (($tag = trim($tag)) === '')
				continue;

			$banned_tag = FALSE;

			$tag_slug = URL::title($tag);
			
			// Check if this post has already this tag
			if ($this->has('tags', ORM::factory('tag', array('slug' => $tag_slug))))
				continue;
			
			$tag_obj = ORM::factory('tag')->get_tag_by_slug($tag_slug);

			// This is a new tag. Add it to the DB
			if ($tag_obj->id === NULL)
			{
				$tag_obj->value = $tag;
				$tag_obj->slug = $tag_slug;
				$tag_obj->created_by = ($this->user_id > 0) ? $this->user_id : $this->created_by;
				$tag_obj->updated_at = time();
			}
			else
			{
				if ($tag_obj->tag_status == Helper_TagStatus::NORMAL)
				{
					$tag_obj->post_count++;
				}
				elseif ($tag_obj->tag_status == Helper_TagStatus::DELETED)
				{
					$tag_obj->post_count++;
					$tag_obj->tag_status = Helper_TagStatus::NORMAL;
				}
				else
				{
					$banned_tag = TRUE;
				}
			}

			if ($banned_tag)
				continue;

			if (!$tag_obj->save())
			{
				Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::add_tags(): ' .
					sprintf('Error while saving tag with name: %s, slug: %s
                          						, id: %d', $tag, $tag_slug, $tag_obj->id));
				continue;
			}

			$this->add('tags', $tag_obj);
		}
	}

	/**
	 * Updates tags for the question.
	 * Searches tag's slug in case that tag is already added. If so increase its used count.
	 * Otherwise, add the tags to the DB. Lastly adds the tag to this question.
	 *
	 * @param string posted tags
	 * @uses  Model_Post::generate_tag_list()
	 * @uses  Model_Post::get_tag_by_slug()
	 * @uses  Model_Post::add_tags()
	 */
	private function update_tags($tags)
	{
		$tag_array = explode(',', $tags);
		$old_tags = $this->generate_tag_list();

		if ($old_tags === $tags)
			return;

		$old_tags_array = ($old_tags === '') ? array() : explode(',', $old_tags);

		// Loop every entered tag
		foreach ($old_tags_array as $tag)
		{
			// If an old tag is entered again (maybe multiple times), just remove it from new tags string
			$tag_found = FALSE;
			$search_old_tags = TRUE;
			while ($search_old_tags)
			{
				$ind = array_search($tag, $tag_array);
				if ($ind === FALSE || $ind < 0)
				{
					$search_old_tags = FALSE;
				}
				else
				{
					$tag_found = TRUE;
					unset($tag_array[$ind]);
				}
			}

			if ($tag_found)
				continue;

			// An old tag is removed when the question is edited. So delete the tag from the question.

			$tag_obj = ORM::factory('tag')->get_tag_by_slug(URL::title($tag));
			$tag_obj->post_count--;

			if ($tag_obj->post_count === 0)
			{
				$tag_obj->tag_status = Helper_TagStatus::DELETED;
			}

			if (!$tag_obj->save())
			{
				Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::add_tags(): ' .
					sprintf('Error while saving tag with name: %s, id: %d', $tag, $tag_obj->id));
				continue;
			}

			$this->remove('tags', $tag_obj);
		}
			
		$tag_array_will_be_added = '';

		// Loop tags that will be added. (Not found in previous revision of the question)
		foreach ($tag_array as $tag)
		{
			$tag_array_will_be_added .= $tag . ',';
		}

		if ($tag_array_will_be_added === '')
			return;
			
		try {
			$this->add_tags(substr($tag_array_will_be_added, 0, -1));
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::add_tags(): ' . $ex->getMessage());
		}
	}

	/**
	 * Checks if the same user has already voted for this post.
	 * If the prev vote is the opposite of the current one, deletes it and update user's reputation
	 * If the same vote is used before, do nothing, inform user.
	 *
	 * @param  int type of the vote. (up or down)
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception
	 * @return int 1 => not voted before, -1 => opposite vote is used before, -2 => the same vote is used before
	 */
	private function check_user_previous_votes($vote_type)
	{
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Post::check_user_previous_votes(): Could not get current user');

		if ($this->post_type === Helper_PostType::QUESTION)
		{
			$previous_vote_up = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
				$this->id, Helper_ReputationType::QUESTION_VOTE_UP);

			$previous_vote_down = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
				$this->id, Helper_ReputationType::QUESTION_VOTE_DOWN);
		}
		elseif ($this->post_type === Helper_PostType::ANSWER)
		{
			$previous_vote_up = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
				$this->id, Helper_ReputationType::ANSWER_VOTE_UP);

			$previous_vote_down = ORM::factory('reputation')->get_user_reputation_for_post($user->id,
				$this->id, Helper_ReputationType::ANSWER_VOTE_DOWN);
		}

		// User has not voted this post before. (up or down)
		if (!$previous_vote_up->loaded() && !$previous_vote_down->loaded())
			return 1;

		// The same vote has already been used
		if (($vote_type === 0 && $previous_vote_down->loaded()) ||
			($vote_type === 1 && $previous_vote_up->loaded()))
			return -2;

		// User has voted this post before but different type.
		// The opposite vote is given by the same user, delete it, update users reputation
		if ($vote_type === 0)
		{
			// update voter's reputation
			$this->handle_reputation($previous_vote_up->reputation_type, TRUE);

			// update owner user's reputation
			if ($previous_vote_up->reputation_type === Helper_ReputationType::QUESTION_VOTE_UP)
			{
				$this->handle_reputation(Helper_ReputationType::OWN_QUESTION_VOTED_UP, TRUE);
			}
			else
			{
				$this->handle_reputation(Helper_ReputationType::OWN_ANSWER_VOTED_UP, TRUE);
			}
				
			$this->up_votes--;
		}
		else
		{
			// update voter's reputation
			$this->handle_reputation($previous_vote_down->reputation_type, TRUE);

			// update owner user's reputation
			if ($previous_vote_down->reputation_type === Helper_ReputationType::QUESTION_VOTE_DOWN)
			{
				$this->handle_reputation(Helper_ReputationType::OWN_QUESTION_VOTED_DOWN, TRUE);
			}
			else
			{
				$this->handle_reputation(Helper_ReputationType::OWN_ANSWER_VOTED_DOWN, TRUE);
			}
				
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
	 * Checks if a question has accepted answer or not
	 *
	 * @return bool true if found
	 */
	private function check_accepted_posts()
	{
		$count = $this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('parent_post_id','=' , $this->parent_post_id)
			->and_where('post_status','=' , Helper_PostStatus::ACCEPTED)
			->count_all();

		return ($count > 0);
	}

	/**
	 * Increases or decreases parent post's relevant (answer or comment count) field
	 *
	 * @param  bool default increases parent question's relevant field
	 * @throws ORM_Validation_Exception
	 */
	private function update_parent_stats($increase = TRUE)
	{
		if (($parent_post = ORM::factory('post')->get($this->parent_post_id, Helper_PostType::ALL)) === NULL)
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::update_parent_stats(): Could not get parent post. ID: ' . $this->id . ', parent ID: ' . $this->parent_post_id);
			return;
		}
			
		$val = ($increase) ? 1 : -1;

		if ($this->post_type === Helper_PostType::ANSWER)
			$parent_post->answer_count += $val;
		elseif ($this->post_type === Helper_PostType::COMMENT)
			$parent_post->comment_count += $val;
		else
			return;

		if (!$parent_post->save())
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Post::update_parent_stats(): Could not save parent post. ID: ' . $parent_post->id);
	}
}