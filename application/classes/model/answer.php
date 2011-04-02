<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Answer Model
 *
 * @package   qamini
 * @uses      Extends Model_Post
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class Model_Answer extends Model_Post {

	/**
	 * Holds the comments of the post
	 *
	 * @var array
	 */
	private $_comments = array();
	
	/**
	 * Getter of comments
	 * 
	 * @return array of Model_Comment instances
	 */
	public function get_comments()
	{
		return $this->_comments;
	}
	
	/**
	 * Adds a new comment to the answer
	 * 
	 * @param object Model_Comment instance
	 */
	public function add_comment($comment)
	{
		$this->_comments[] = $comment;
	}

	/**
	 * Returns post by id
	 *
	 * @param  int    post id
	 * @return object instance of Model_Post
	 */
	public static function get($id)
	{
		$post = ORM::factory('answer')
			->where('id', '=', $id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_type','=' , Model_Post::ANSWER)->find();
			
		if (!$post->loaded())
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Get::Could not fetch the answer by ID: ' . $id);
			return NULL;
		}
		
		return $post;
	}
	
  	/**
	 * Returns answer by id
	 *
	 * @param  int               post id
	 * @param  object            instance of Model_User
	 * @throws Kohana_Exception
	 * @return object            instance of Model_Answer
	 */
	public static function get_user_answer_by_id($id, $user)
	{
		$answer = ORM::factory('answer')->where('id', '=', $id)
			->and_where('user_id', '=', $user->id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_type','=' , Model_Post::ANSWER)->find();
			
		if (!$answer->loaded())
			throw new Kohana_Exception(
				sprintf('get_user_answer_by_id::Could not fetch the answer by ID: %d for user ID: %d', $id, $user->id));
				
		return $answer;
	}
	
	/**
	 * Returns answers of the parent question
	 *
	 * @param  int    page size
	 * @uses   Model_Answer::create_object()
	 * @return array  Model_Answer objects
	 */
	public static function get_answers($parent_post_id)
	{
		$answers = array();
				
		$db_result = ORM::factory('answer')
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::IN_REVIEW)
			->and_where('post_type', '=', Model_Post::ANSWER)
			->and_where('parent_post_id', '=', $parent_post_id)
			->order_by('latest_activity', 'desc')
			->find_all();

		foreach ($db_result as $answer)
		{
			// If the answer is accepted, add it to the top of the answers array
			if ($answer->is_accepted())
				array_unshift($answers, Model_Answer::create_object($answer));
			else
				$answers[] = Model_Answer::create_object($answer);
		}
		
		return $answers;
	}
	
	/**
	 * Checks if answer is accepted
	 * 
	 * @return boolean
	 */
	private function is_accepted()
	{
		return $this->post_status === Helper_PostStatus::ACCEPTED;
	}

	/**
	 * Adds a new answer
	 *
	 * @param  array New answer data
	 * @param  int Question id
	 * @uses   Model_Post::create_post()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return boolean true on success
	 */
	public function insert($post, $question_id)
	{
		$this->parent_post_id = $question_id;
		$this->post_type = Model_Post::ANSWER;
				
		$this->create_post($post);
			
		return TRUE;
	}
	
	/**
	 * Calls parent to save answer, handles reputation
	 *
	 * @param  array posted data
	 */	
	protected function create_post($post)
	{		
		parent::create_post($post);

		$this->update_parent_answer_count();
		
		if (Arr::get($post, 'user_id', 0) > 0)
			$this->handle_reputation(Model_Reputation::ANSWER_ADD);
	}
	
	/**
	 * Increases or decreases parent post's answer count field
	 *
	 * @param  bool increase / decrease
	 * @uses   Model_Post::update_parent_stats()
	 */
	private function update_parent_answer_count($increase = TRUE)
	{			
		try {
			$this->update_parent_stats('answer_count', $increase);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, $ex->getMessage());
		}
	}

	/**
	 * Used to edit an answer
	 *
	 * @param  array posted answer data
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Question::get()
	 * @uses   Model_User::update_last_activity_time()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return object
	 */
	public function edit($post)
	{
		// Currently only logged in users can edit answers.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Answer::edit(): Could not get current user');

		$this->save_post($post);
			
		$user->update_last_activity_time();

		if (($parent_post = Model_Question::get($this->parent_post_id)) === NULL)	return NULL;

		return $parent_post;
	}

	/**
	 * Deletes an answer
	 *
	 * @uses   Model_Post::mark_post_anonymous()
	 * @uses   Model_Post::handle_reputation()
	 * @uses   Model_Question::get()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return object
	 */
	public function delete()
	{
		// Currently only logged in users can delete answers.
		if (($user = Auth::instance()->get_user()) === FALSE)
			throw new Kohana_Exception('Model_Answer::delete(): Could not get current user');

		$this->mark_post_anonymous();

		$this->handle_reputation(Model_Reputation::ANSWER_ADD, true);
			
		if (($parent_post = Model_Question::get($this->parent_post_id)) === NULL)
			return NULL;

		return $parent_post;
	}
	
	/**
	 * Increase upvote / downvote count of the post and handles reputation
	 *
	 * @param  int vote type 0 for down, 1 for up votes
	 * @uses   Model_Post::check_user_previous_votes()
	 * @uses   Model_Post::vote_post()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return int 1 => Success, -2 => User is already voted
	 */
	public function vote($vote_type)
	{
		// Check if user voted this post before, if so do the appropriate actions.
		$previous_votes = $this->check_user_previous_votes_for_post($vote_type);

		if ($previous_votes !== 1)	return $previous_votes;

		if ($vote_type === 0)
		{
			$this->down_votes++;

			$reputation_type = Model_Reputation::ANSWER_VOTE_DOWN;
			$reputation_type_owner = Model_Reputation::OWN_ANSWER_VOTED_DOWN;
		}
		else
		{
			$this->up_votes++;

			$reputation_type = Model_Reputation::ANSWER_VOTE_UP;
			$reputation_type_owner = Model_Reputation::OWN_ANSWER_VOTED_UP;
		}
		
		return parent::vote_post($reputation_type, $reputation_type_owner);
	}
	
 	/**
	 * Prepares parameters and calls parent's method.
	 *
	 * @param  int type of the vote. (up or down)
	 * @throws Kohana_Exception
	 * @return int 1 => not voted before, -1 => opposite vote is used before, -2 => the same vote is used before
	 */
	protected function check_user_previous_votes_for_post($vote_type)
	{
		return $this->check_user_previous_votes($vote_type, Model_Reputation::ANSWER_VOTE_UP
			, Model_Reputation::ANSWER_VOTE_DOWN);
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
			throw new Kohana_Exception('Model_Answer::accept_post(): Could not get current user');
			
		if ($this->is_deleted())	return -1;

		// Get the parent question to check its creator is the same as current user
		if (($question = Model_Question::get($this->parent_post_id)) === NULL || $question->user_id !== $user->id)
			return -1;

		// If the post has been accepted before, undo accept
		if ($this->is_accepted())
		{
			$this->process_undo_accept();
			return 2;
		}

		// Check if another answer is already chosen as accepted answer
		if ($this->has_accepted_answers())	return -2;

		$this->process_accept();

		return 1;
	}
	
	/*
	 * Undo accepts an answer
	 */
	private function process_undo_accept()
	{
		$this->post_status = Helper_PostStatus::PUBLISHED;
		$this->save();

		$this->handle_reputation(Model_Reputation::ACCEPTED_ANSWER, TRUE);
		$this->handle_reputation(Model_Reputation::OWN_ACCEPTED_ANSWER, TRUE);
	}
	
	/**
	 * Process accepts an answer
	 */
	private function process_accept()
	{
		$this->post_status = Helper_PostStatus::ACCEPTED;
		$this->save();

		$this->handle_reputation(Model_Reputation::ACCEPTED_ANSWER);
		$this->handle_reputation(Model_Reputation::OWN_ACCEPTED_ANSWER);
	}
	
	/**
	 * Checks if a question has accepted answer or not
	 *
	 * @return boolean
	 */
	private function has_accepted_answers()
	{
		$count = $this->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('parent_post_id','=' , $this->parent_post_id)
			->and_where('post_status','=' , Helper_PostStatus::ACCEPTED)
			->count_all();

		return $count > 0;
	}

	/**
	 * Creates a new Model_Answer object from an associative array
	 *
	 * @param  array data
	 * @return object Instance of Model_Answer
	 */
	private static function create_object($data)
	{
		$new_object = new Model_Answer;
		foreach ($data as $key => $value)
		{
			$new_object->$key = $value;
		}

		return $new_object;
	}
}