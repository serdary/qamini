<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Comment Model
 *
 * @package   qamini
 * @uses      Extends Model_Post
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class Model_Comment extends Model_Post {

	/**
	 * Returns comment by id
	 *
	 * @param  int     post id
	 * @param  boolean false for cms
	 * @return object  instance of Model_Comment
	 */
	public static function get($id, $only_moderated = TRUE)
	{			
		$post = $only_moderated
			? self::get_moderated_comment($id)
			: self::get_comment_for_cms($id);
			
		if (!$post->loaded())
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Get::Could not fetch the comment by ID: ' . $id);
			return NULL;
		}

		return $post;
	}
	
	/**
	 * Gets moderated comment
	 * 
	 * @param  int     post id
	 * @return object  instance of Model_Comment
	 */
	public static function get_moderated_comment($id)
	{
		return ORM::factory('comment')
			->where('id', '=', $id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type','=' , Helper_PostType::COMMENT)->find();
	}
	
	/**
	 * Gets comment without checking if it is moderated or not
	 * 
	 * @param  int     post id
	 * @return object  instance of Model_Comment
	 */	
	public static function get_comment_for_cms($id)
	{
		return ORM::factory('comment')
			->where('id', '=', $id)
			->and_where('post_type','=' , Helper_PostType::COMMENT)->find();
	}
	
  	/**
	 * Returns user's comment by id
	 *
	 * @param  int post id
	 * @param  object instance of Model_User
	 * @throws Kohana_Exception
	 * @return object
	 */
	public static function get_user_comment_by_id($id, $user)
	{
		$comment = ORM::factory('comment')->where('id', '=', $id)
			->and_where('user_id', '=', $user->id)
			->and_where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_type','=' , Helper_PostType::COMMENT)->find();
			
		if (!$comment->loaded())
			throw new Kohana_Exception(
				sprintf('get_user_comment_by_id::Could not fetch the post by ID: %d for user ID: %d', $id, $user->id));
				
		return $comment;
	}

	/**
	 * Adds new comment to a post (Question Or Answer)
	 *
	 * @param  array new answer data
	 * @param  int   parent id
	 * @uses   Model_Post::create_post()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 * @return boolean
	 */
	public function insert($post, $parent_id)
	{
		if (! Model_User::check_user_has_write_access($user))
			throw new Kohana_Exception('Model_Comment::add(): Could not get current user');
	
		$post['user_id'] = $user->id;
		
		$this->parent_post_id = $parent_id;
		
		$this->create_post($post);
		
		return TRUE;
	}
	
	/**
	 * Calls parent to save comment, handles reputation
	 *
	 * @uses   Model_Post::handle_reputation()
	 * @param  array posted data
	 */	
	protected function create_post($post)
	{
		$this->post_type = Helper_PostType::COMMENT;
		
		parent::create_post($post);

		$this->update_parent_comment_count();
		
		if (Arr::get($post, 'user_id', 0) > 0)
			$this->handle_reputation(Model_Reputation::COMMENT_ADD);
	}
	
	/**
	 * Increases or decreases parent post's comment count field
	 *
	 * @param  bool increase / decrease
	 * @uses   Model_Post::update_parent_stats()
	 */
	private function update_parent_comment_count($increase = TRUE)
	{			
		try {
			$this->update_parent_stats(Helper_PostType::COMMENT, $increase);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, $ex->getMessage());
		}
	}

	/**
	 * Used to delete a comment
	 *
	 * @uses   Model_Post::handle_reputation()
	 * @uses   Model_Comment::update_parent_comment_count()
	 * @throws Kohana_Exception, ORM_Validation_Exception
	 */
	public function delete()
	{
		if (! Model_User::check_user_has_write_access())
			throw new Kohana_Exception('Model_Comment::delete(): Could not get current user');

		$this->latest_activity = time();
		$this->post_moderation = Helper_PostModeration::DELETED;

		if (!$this->save())
			throw new Kohana_Exception("Model_Comment::delete(): Could not delete comment with ID: $this->id");
			
		$this->handle_reputation(Model_Reputation::COMMENT_ADD, true);

		$this->update_parent_comment_count(FALSE);
		
		Kohana_Log::instance()->add(Kohana_Log::INFO, 'COMMENT_DELETE: ' . $this->id);
	}
	
	/**
	 * Gets comments of the parent post
	 */
	public static function get_comments($parent)
	{
		$parent_ids = array();
		$parent_ids[] = $parent->id;

		foreach ($parent->get_answers() as $answer)
		{
			$parent_ids[] = $answer->id;
		}

		$results = ORM::factory('comment')
			->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->and_where('post_type', '=', Helper_PostType::COMMENT)
			->and_where('parent_post_id', 'IN', DB::Expr(sprintf('(%s)', implode(',', $parent_ids))))
			->order_by('created_at', 'asc')
			->find_all();
			
		$comments = array();

		foreach ($results as $comment)
		{
			if ($comment->comment_belongs_question($parent->id))
			{
				$comments[] = $comment;
				continue;
			}
			
			foreach ($parent->get_answers() as $answer)
			{
				if ($comment->parent_post_id === $answer->id)
				{
					$answer->add_comment($comment);
					break;
				}
			}
		}
		
		$parent->set_comments($comments);
	}
	
	/**
	 * Checks if the comment belongs a question or not
	 * 
	 * @param  id of the parent
	 * @return boolean
	 */
	private function comment_belongs_question($id)
	{
		return $this->parent_post_id === $id;
	}
}