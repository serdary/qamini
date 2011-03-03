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

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Add Comment Action. Only registered users can post a comment.
	 *
	 * @uses Model_Post::add_comment()
	 */
	public function action_add()
	{
		if (!empty($this->errors))
			return;

		$post = $this->check_post_form();

		// If any error occured while checking post values, then encode errors, and stop action
		if (!empty($this->errors))
			return;

		$parent_id = (int) $post['hdn_parent_id'];
			
		// Try to save the comment
		try {
			$new_comment_id = ORM::factory('post')->add_comment($post, $parent_id);
		}
		catch (ORM_Validation_Exception $ex)
		{
			//$this->prepare_error_response($ex->errors('models'));
			$this->prepare_error_response('Content must be at least 20 characters long.');
			return;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Comment | Message: ' . $ex->getMessage());
				
			$this->prepare_error_response(__('Error occured, please try again.'));
			return;
		}

		$comment_delete_link = HTML::anchor(Route::get('comment')->uri(array('action' => 'delete'
				, 'id' => $new_comment_id, 'parent_id' => $parent_id)), 'Delete'
				, array('onclick' => 'return Detail.DeleteComment(' . $parent_id .  ',' . $new_comment_id . ')'
				, 'class' => 'comment-delete-' . $new_comment_id));

		$this->response->body(json_encode(
			array('result' => TRUE, 'message' => __('Comment successfully added'),
				'comment_link' => $comment_delete_link, 'id' => $new_comment_id)));
	}

	/**
	 * Delete Comment Action. Only registered users can delete a comment.
	 *
	 * @uses Model_User::get_post_by_id()
	 * @uses Model_Post::delete_comment()
	 */
	public function action_delete()
	{
		if (!empty($this->errors))
			return;
			
		// Add error if post is null or values are not correct
		if ((!$post = $_POST) || !isset($_POST['parent_id']) || !isset($_POST['comment_id'])
			|| !is_numeric($_POST['parent_id']) || !is_numeric($_POST['comment_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
		}
		else
		{
			$parent_id = (int) $post['parent_id'];
			$comment_id = (int) $post['comment_id'];
		}

		// If any error occured while checking post values, then encode errors, and stop action
		if (!empty($this->errors))
			return;

		// Try to get and delete user comment
		try {
			$comment = $this->user->get_post_by_id($comment_id, Helper_PostType::COMMENT);
			$comment->delete_comment();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Delete Comment | Message: ' . $ex->getMessage());
				
			$this->prepare_error_response('Error occured, please try again.');
			return;
		}

		$this->response->body(json_encode(
			array('result' => 'OK', 'message' => __('Comment successfully deleted'))));
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Checks posted comment, add errors if any found
	 */
	private function check_post_form()
	{
		// If posted data is invalid, add error.
		if ((!$post = $_POST) || !isset($_POST['hdn_parent_id']) || !is_numeric($_POST['hdn_parent_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return NULL;
		}

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
}
