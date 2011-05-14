<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Question Model
 *
 * @package   qamini
 * @uses      Extends Model_Post
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class Model_Question extends Model_Post {
	
	/**
	 * Holds the answers of the post
	 *
	 * @var array
	 */
	private $_answers = array();

	/**
	 * Holds the comments of the post
	 *
	 * @var array
	 */
	private $_comments = array();
	
	/**
	 * Getter of answers
	 * 
	 * @return array of Model_Answer instances
	 */
	public function get_answers()
	{
		return $this->_answers;
	}
	
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
	 * Setter of comments
	 * 
	 * @param array of Model_Comment instances
	 */
	public function set_comments($comments)
	{
		$this->_comments = $comments;
	}
	
	/**
	 * Returns post by id and type
	 *
	 * @param  int     post id
	 * @param  boolean false for cms
	 * @return object  instance of Model_Question
	 */
	public static function get($id, $only_moderated = TRUE)
	{
		if ($only_moderated)	$post = self::get_moderated_question($id);
		else $post = self::get_question_for_cms($id);
					
		if (!$post->loaded())
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Get::Could not fetch the question by ID: ' . $id);
			return NULL;
		}

		return $post;
	}
	
	
	/**
	 * Gets moderated question
	 * 
	 * @param  int    post id
	 * @return object instance of Model_Question
	 */
	public static function get_moderated_question($id)
	{			
		return ORM::factory('question')
			->where('id', '=', $id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type','=' , Helper_PostType::QUESTION)->find();
	}
	
	/**
	 * Gets question without checking if it is moderated or not
	 * 
	 * @param  int    post id
	 * @return object instance of Model_Question
	 */	
	public static function get_question_for_cms($id)
	{
		return ORM::factory('question')
			->where('id', '=', $id)
			->and_where('post_type','=' , Helper_PostType::QUESTION)->find();
	}
	
  	/**
	 * Returns user's question by id
	 *
	 * @param  int               post id
	 * @param  object            instance of Model_User
	 * @throws Kohana_Exception
	 * @return object            instance of Model_Question
	 */
	public static function get_user_question_by_id($id, $user)
	{
		if($user->has('roles', ORM::factory('role', array('name' => 'admin'))))
		{
			return Model_Question::get($id, FALSE);
		}
		else
		{
			$question = ORM::factory('question')->where('id', '=', $id)
				->and_where('user_id', '=', $user->id)
				->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
				->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
				->and_where('post_type','=' , Helper_PostType::QUESTION)->find();
		}	
		if (!$question->loaded())
			throw new Kohana_Exception(
				sprintf('get_user_question_by_id::Could not fetch the post by ID: %d for user ID: %d', $id, $user->id));
				
		return $question;
	}

	/**
	 * Returns questions according to post status, page size and offset
	 *
	 * @param  int    page size
	 * @param  int    offset
	 * @param  string status of the posts that will be count
	 * @return array  Model_Question objects
	 */
	public static function get_questions($page_size, $offset, $status = Helper_PostStatus::ALL)
	{
		switch ($status)
		{
			case Helper_PostStatus::ANSWERED:
				return Model_Question::get_answered_questions($page_size, $offset);
			case Helper_PostStatus::UNANSWERED:
				return Model_Question::get_unanswered_questions($page_size, $offset);
			default:
				return Model_Question::get_all_questions($page_size, $offset);
		}
	}
	
	/**
	 * Returns answered questions by page size and offset
	 * 
	 * @param  int    page size
	 * @param  int    offset
	 * @return array  Model_Question objects
	 */
	private static function get_answered_questions($page_size, $offset)
	{
		return ORM::factory('question')->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->limit($page_size)->offset($offset)
			->order_by('answer_count', 'desc')->order_by('latest_activity', 'desc')->find_all();
	}

	/**
	 * Returns unanswered questions by page size and offset
	 * 
	 * @param  int    page size
	 * @param  int    offset
	 * @return array  Model_Question objects
	 */
	private static function get_unanswered_questions($page_size, $offset)
	{
		return ORM::factory('question')->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->limit($page_size)->offset($offset)->and_where('answer_count', '=', 0)
			->order_by('latest_activity', 'desc')->find_all();
	}
	
	/**
	 * Returns all questions by page size and offset
	 * 
	 * @param  int    page size
	 * @param  int    offset
	 * @return array  Model_Question objects
	 */
	private static function get_all_questions($page_size, $offset)
	{
		return ORM::factory('question')->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type', '=', Helper_PostType::QUESTION)
			->limit($page_size)->offset($offset)
			->order_by('latest_activity', 'desc')->find_all();
	}

	/**
	 * Adds a new question
	 *
	 * @param  array new question data
	 * @uses   Model_Post::create_post()
	 * @uses   Model_Question::add_tags()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return boolean
	 */
	public function insert($post)
	{
		$this->create_post($post);

		$this->process_tags_if_posted($post);

		return TRUE;
	}
	
	/**
	 * Process tags of the question if provided data
	 * 
	 * @param array post data
	 * @param string process type
	 */
	private function process_tags_if_posted($post, $process = 'add')
	{
		if (!isset($post['tags']))	return;
		
		if ($process === 'add')			$this->add_tags($post['tags']);
		elseif ($process === 'update')	$this->update_tags($post['tags']);
	}
	
	/**
	 * Calls parent to save question, handles reputation
	 *
	 * @param  array posted data
	 */	
	protected function create_post($post)
	{
		$this->post_type = Helper_PostType::QUESTION;
		
		parent::create_post($post);
		
		if (Arr::get($post, 'user_id', 0) > 0)
			$this->handle_reputation(Model_Reputation::QUESTION_ADD);
	}

	/**
	 * Used to edit a question
	 *
	 * @param  array posted question data
	 * @uses   Model_Post::save_post()
	 * @uses   Model_Question::update_tags()
	 * @uses   Model_User::update_last_activity_time()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return boolean
	 */
	public function edit($post)
	{
		// Only logged in users and admins can modify questions.
		$user = new Model_User;
		if (!$this->user_can_modify($user))
			throw new Kohana_Exception('Model_Question::edit(): Could not get current user');

		$this->check_updated_by_admin($user);
		
		$this->save_post($post);
		
		$this->process_tags_if_posted($post, 'update');
			
		$user->update_last_activity_time();
			
		return TRUE;
	}

	/**
	 * Used to delete a question
	 *
	 * @uses   Model_Post::mark_post_anonymous()
	 * @uses   Model_Post::handle_reputation()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	public function delete()
	{
		// Currently only logged in users can delete questions.
		if (($user = Auth::instance()->get_user()) === NULL)
			throw new Kohana_Exception('Model_Question::delete(): Could not get current user');

		// The question marked anonymous (user_id = 0) instead of marked deleted.
		$this->mark_post_anonymous();

		$this->handle_reputation(Model_Reputation::QUESTION_ADD, true);
	}
	
	/**
	 * Gets answers of the question
	 *
	 * @uses Model_Answer::get_answers()
	 */
	public function load_answers()
	{
		$this->_answers = Model_Answer::get_answers($this->id);
	}

	/**
	 * Gets answers and comments of the question
	 *
	 * @uses Model_Question::load_answers()
	 * @uses Model_Comment::get_comments()
	 */
	public function load_answers_and_comments()
	{
		$this->load_answers();
		
		Model_Comment::get_comments($this);
	}

	/**
	 * Returns a question's tags seperated with a comma
	 * Used for editing question action
	 * 
	 * @return string tag list 
	 */
	public function generate_tag_list()
	{
		$tag_list = '';
		foreach ($this->get_tags() as $tag)
		{
			$tag_list .= $tag->value . ',';
		}

		return ($tag_list === '') ? $tag_list : substr($tag_list, 0, -1);
	}

	/**
	 * Checks the question title
	 *
	 * @param  array values to check
	 * @param  array reference of errors
	 * @return boolean
	 */
	public function check_question_title(&$post, &$errors)
	{
		if (!isset($post['title']))	return FALSE;
		
		$post['title'] = trim($post['title']);

		if ($post['title'] !== '' && (strlen($post['title']) > 10))	return TRUE;
			
		$errors += array('question_' => __('Question title must be at least 10 characters long.'));
		return FALSE;
	}

	/* Search Methods */

	/**
	 * Counts posts which are relevant to query string
	 *
	 * @param  string query string
	 * @return int result count
	 */
	public static function count_search_results($query)
	{
		if ($query === NULL || $query === '')	return 0;
			
		return ORM::factory('question')->and_where_open()
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
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
	 * @return array  Model_Question objects
	 */
	public static function search($query, $page_size, $offset)
	{
		return ORM::factory('question')->and_where_open()
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
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
	 * Increase upvote / downvote count of the post and handles reputation
	 *
	 * @param  int vote type 0 for down, 1 for up votes
	 * @uses   Model_Post::check_user_previous_votes()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return int 1 => Success, -2 => User is already voted
	 */
	public function vote($vote_type)
	{
		$previous_votes = $this->check_user_previous_votes_for_post($vote_type);

		if ($previous_votes !== 1)	return $previous_votes;

		if ($vote_type === 0)
		{
			$this->down_votes++;

			$reputation_type = Model_Reputation::QUESTION_VOTE_DOWN;
			$reputation_type_owner = Model_Reputation::OWN_QUESTION_VOTED_DOWN;
		}
		else
		{
			$this->up_votes++;

			$reputation_type = Model_Reputation::QUESTION_VOTE_UP;
			$reputation_type_owner = Model_Reputation::OWN_QUESTION_VOTED_UP;
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
		return $this->check_user_previous_votes($vote_type, Model_Reputation::QUESTION_VOTE_UP
			, Model_Reputation::QUESTION_VOTE_DOWN);
	}

	/**
	 * Checks if the tag is attached to the question
	 * 
	 * @param string tag
	 * @return boolean
	 */
	private function has_tag($tag)
	{
		return ORM::factory('post', $this->id)->has('tags', ORM::factory('tag', array('slug' => URL::title($tag))));
	}
	
	/**
	 * Adds tags to the question.
	 * Searches tag's slug in case that tag is already added. If so increase its used count.
	 * Otherwise, add the tags to the DB. Lastly adds the tag to this question.
	 *
	 * @param  string posted tags
	 * @uses   Model_Tag::get_tag()
	 */
	private function add_tags($tags)
	{
		if ($tags === '')	return;

		$tag_array = explode(',', $tags);

		foreach ($tag_array as $tag)
		{
			if (($tag = trim($tag)) === '')	continue;
			
			if ($this->has_tag($tag))	continue;
			
			$tag_obj = ORM::factory('tag')->get_tag($tag);

			// This is a new tag. Add it to the DB
			if ($tag_obj->id === NULL)
			{
				$tag_obj = Model_Tag::create_object($tag, $this->generate_created_by_for_tag(), time());
			}
			else	$tag_obj->process_add_to_new_question();

			if ($tag_obj->is_banned())	continue;

			try {		
				if (!$tag_obj->save())
				{
					Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Question::add_tags(): ' .
						sprintf('Error while saving tag with name: %s: %s, id: %d', $tag, $tag_obj->id));
					continue;
				}
	
				ORM::factory('post', $this->id)->add('tags', $tag_obj);
			}
			catch (Exception $ex) {
				Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Question::add_tags(): ' . $ex->getMessage());
			}
		}
	}
	
	/**
	 * Generates a created by string for the tag
	 * 
	 * @return string
	 */
	private function generate_created_by_for_tag()
	{
		return ($this->user_id > 0) ? 'userid_' . $this->user_id : $this->created_by;
	}

	/**
	 * Updates tags for the question.
	 * Searches tag's slug in case that tag is already added. If so increase its used count.
	 * Otherwise, add the tags to the DB. Lastly adds the tag to this question.
	 *
	 * @param string posted tags
	 * @uses  Model_Question::generate_tag_list()
	 * @uses  Model_Tag::get_tag()
	 * @uses  Model_Question::add_tags()
	 */
	private function update_tags($tags)
	{
		$tag_array = explode(',', $tags);
		$old_tags = $this->generate_tag_list();

		if ($old_tags === $tags)	return;

		$old_tags_array = ($old_tags === '') ? array() : explode(',', $old_tags);

		foreach ($old_tags_array as $tag)
		{
			if ($this->remove_old_tag_from_array($tag, $tag_array))	continue;

			// An old tag is removed when the question is edited. So delete the tag from the question.
			$tag_obj = ORM::factory('tag')->get_tag($tag);
			$tag_obj->decrease_post_count();
			
			$this->remove_tag($tag_obj);
		}
	
		$this->add_tags($this->prepare_new_tags_string($tag_array));
	}
	
	/**
	 * Searches tag in new entered tag array. If tag is found (maybe multiple times), it will be deleted
	 * 
	 * @param string tag that is searched
	 * @param array tags
	 * @return boolean
	 */
	private function remove_old_tag_from_array($tag, &$tag_array)
	{
		$tag_found = FALSE;
		while (TRUE)
		{
			$ind = array_search($tag, $tag_array);
			if ($ind === FALSE || $ind < 0)	break;

			$tag_found = TRUE;
			unset($tag_array[$ind]);
		}

		return $tag_found;
	}
	
	/**
	 * Removes a tag from question
	 * 
	 * @param object tag
	 */
	private function remove_tag($tag_obj)
	{
		try {		
			if (!$tag_obj->save())
			{
				Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Question::remove_tag(): ' .
					sprintf('Error while saving tag with name: %s, id: %d', $tag_obj->value, $tag_obj->id));
				return;
			}

			ORM::factory('post', $this->id)->remove('tags', $tag_obj);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Question::remove_tag(): ' . $ex->getMessage());
		}
	}
	
	/**
	 * Prepares a new string froma tags array
	 * 
	 * @param array tags
	 * @return string
	 */
	private function prepare_new_tags_string($tags)
	{
		$new_tags = '';

		foreach ($tags as $tag)
			$new_tags .= $tag . ',';
			
		return ($new_tags === '') ? $new_tags : substr($new_tags, 0, -1);
	}
}