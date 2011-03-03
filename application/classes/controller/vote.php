<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Voting Controller to vote, accept posts etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Basic_Ajax
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Vote extends Controller_Basic_Ajax {

	/**
	 * Calls parent's before method and check login of the current user
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Up / down voting processes are handled in this action method.
	 *
	 * @uses Model_Post::vote_post()
	 */
	public function action_vote()
	{
		if (!empty($this->errors))
			return;
			
		// If posted data is invalid, add error.
		if (!$_POST || !isset($_POST['post_id']) || !is_numeric($_POST['post_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return;
		}

		$post_id = (int) $_POST['post_id'];
		$vote_type = (int) $_POST['vote_type'];

		$post_type = (isset($_POST['post_type']) && $_POST['post_type'] === 'A')
			? Helper_PostType::ANSWER
			: Helper_PostType::QUESTION;

		if (($post = ORM::factory('post')->get($post_id, $post_type)) === NULL)
		{
			$this->prepare_error_response(__('Post is not available'));
				return;
		}

		// Check if the voter and post owner is the same person or not
		$user = Auth::instance()->get_user();
		if ($post->user_id === $user->id)
		{
			$this->prepare_error_response(__('You cannot vote on your own posts.'));
			return;
		}
			
		// Try to vote the post
		try {
			$result = $post->vote_post($vote_type);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Vote | ' . $ex->getMessage());
				
			// If any error occured during getting post from DB or voting post, give error
			$this->prepare_error_response(__('Error Occured'));
			return;
		}

		switch ($result)
		{
			case 1:
					$this->response->body(json_encode(array('result' => $result
						, 'message' => __('Voting succeeded'))));
				break;
			case -1:
					$this->response->body(json_encode(array('result' => $result
						, 'message' => __('Vote has been updated'))));
				break;
			case -2:
					$this->prepare_error_response(__('Already Voted'));
				break;
			default:
					$this->prepare_error_response(__('Error Occured'));
				break;
		}
	}

	/**
	 * Accept / Undo accept an answer action
	 *
	 * @uses Model_Post::accept_post()
	 */
	public function action_accept()
	{
		if (!empty($this->errors))
			return;
			
		// If posted data is invalid, add error.
		if (!$_POST || !isset($_POST['post_id']) || !is_numeric($_POST['post_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return;
		}

		$post_id = (int) $_POST['post_id'];

		// Only answers are being accepted
		if (($post = ORM::factory('post')->get($post_id, Helper_PostType::ANSWER)) === NULL)
		{
			$this->prepare_error_response(__('Post is not available'));
			return;
		}

		// Check if current user and post owner is the same person or not
		$user = Auth::instance()->get_user();
		if ($post->user_id === $user->id)
		{
			$this->prepare_error_response(__('You cannot accept your own posts.'));
			return;
		}
			
		// Try to accept the post
		try {
			$result = $post->accept_post();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Accept Post | ' . $ex->getMessage());
				
			// If any error occured during getting post from DB or accepting post, give error
			$this->prepare_error_response(__('Error Occured'));
			return;
		}

		switch ($result)
		{
			case 1:
					$this->response->body(json_encode(array('result' => $result
						, 'message' => __('Successfully Accepted'))));
				break;
			case 2:
					$this->response->body(json_encode(array('result' => $result
						, 'message' => __('Undo Accepted Answer'))));
				break;
			case -2:
					$this->response->body(json_encode(array('result' => $result
						, 'message' => __('Another answer is already accepted'))));
				break;
			default:
					$this->prepare_error_response(__('Error Occured'));
				break;
		}
	}
}
