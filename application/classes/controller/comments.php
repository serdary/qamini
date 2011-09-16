<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Comment Controller for adding new comments etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Basic_Ajax
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Comments extends Controller_Basic_Ajax {

	public function before()
	{
		parent::before();
	}

	/**
	 * Add Comment Action. Only registered users can post a comment.
	 *
	 * @uses Model_Comment::add()
	 */
	public function action_add()
	{
		if ($this->has_errors())	return;

		$post = $this->check_post_form();

		if ($this->has_errors())	return;

		$parent_id = (int) $post['hdn_parent_id'];

		$comment = new Model_Comment;
		
		if (!$this->process_add_comment($comment, $post))	return;
		
		Kohana_Log::instance()->add(Kohana_Log::INFO
			, sprintf("Controller-Comment Add:: %d user added C Id: %d", $this->user->id, $comment->id));
		
		$this->prepare_add_success_response($comment);
	}

	/**
	 * Delete Comment Action. Only registered users can delete a comment.
	 *
	 * @uses Model_Comment::delete()
	 */
	public function action_delete()
	{
		if ($this->has_errors())	return;
		
		if ($this->invalid_data_for_delete_action() === TRUE)
		{
			$this->prepare_error_response(__('Invalid Request'));
			return;
		}
			
		$post = $_POST;
		$parent_id = (int) $post['parent_id'];
		$comment_id = (int) $post['comment_id'];
		
		if (!$this->process_delete_comment($comment_id, $post))	return;
		
		Kohana_Log::instance()->add(Kohana_Log::INFO
			, sprintf("Controller-Comment Delete:: %d user deleted C Id: %d", $this->user->id, $comment_id));

		$this->response->body(json_encode(
			array('result' => 'OK', 'message' => __('Comment successfully deleted'))));
	}

	/***** PRIVATE METHODS *****/
	
	/**
	 * Checks if current request has errors
	 * 
	 * @return boolean
	 */
	private function has_errors()
	{
		return !empty($this->errors);
	}

	/**
	 * Checks posted comment, add errors if any found
	 * 
	 * @return array
	 */
	private function check_post_form()
	{
		if (!$_POST || !isset($_POST['hdn_parent_id']) || !is_numeric($_POST['hdn_parent_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return NULL;
		}

		$post = $_POST;
		$parent_id = (int) $post['hdn_parent_id'];

		// change content key for the content value
		$content_field_name = 'content_' . $parent_id;
		$post['content'] = $post[$content_field_name];

		$data = Validation::factory($post)->rule('content', 'not_empty');
			
		if (!$data->check())
		{
			$this->prepare_error_response(__('Comment content must be filled.'));
			return NULL;
		}

		return $post;
	}
	
	/**
	 * Do process for adding comment. Updates it on the DB.
	 * 
	 * @param  object reference of Model_Comment
	 * @param  array data
	 * @return boolean
	 */
	private function process_add_comment(&$comment, $post)
	{
		$parent_id = (int) $post['hdn_parent_id'];
		
		try {
			$add_result = $comment->insert($post, $parent_id);
		}
		catch (ORM_Validation_Exception $ex)
		{
			//$this->prepare_error_response($ex->errors('models'));
			$this->prepare_error_response('Content must be at least 20 characters long.');
			return FALSE;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Comment | Message: ' . $ex->getMessage());
				
			$this->prepare_error_response(__('Error occured, please try again.'));
			return FALSE;
		}
		
		return $add_result;
	}
	
	/**
	 * Prepares response body
	 * 
	 * @param object Model_Comment instance
	 */
	private function prepare_add_success_response($comment)
	{
		$this->response->body(json_encode(
			array('result' => TRUE, 'message' => __('Comment successfully added'),
				'comment_link' => $this->create_comment_delete_link($comment), 'id' => $comment->id)));
	}
	
	/**
	 * Creates a comment delete link for the newly added comment
	 * 
	 * @param object Model_Comment instance
	 * @return string
	 */
	private function create_comment_delete_link($comment)
	{		
		return HTML::anchor(Route::get('comment')->uri(array('action' => 'delete'
				, 'id' => $comment->id, 'parent_id' => $comment->parent_post_id)), __('Delete')
				, array('onclick' => 'return Detail.DeleteComment(' . $comment->parent_post_id . ',' . $comment->id . ')'
				, 'class' => 'comment-delete-' . $comment->id));
	}
	
	/**
	 * Checks posted data for delete action
	 * 
	 * @return boolean
	 */
	private function invalid_data_for_delete_action()
	{
		return (!$_POST || !isset($_POST['parent_id']) || !isset($_POST['comment_id'])
				|| !is_numeric($_POST['parent_id']) || !is_numeric($_POST['comment_id']));
	}
	
	/**
	 * Do process for deleting comment
	 * 
	 * @uses   Model_Comment::get_user_comment_by_id()
	 * @param  int comment id
	 * @return boolean
	 */
	private function process_delete_comment($comment_id)
	{
		try {
			Model_Comment::get_user_comment_by_id($comment_id, $this->user)->delete();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Delete Comment | Message: ' . $ex->getMessage());
				
			$this->prepare_error_response('Error occured, please try again.');
			return FALSE;
		}
		
		return TRUE;
	}
}
