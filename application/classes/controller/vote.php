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

	public function before()
	{
		parent::before();
	}

	/**
	 * Up / down voting processes are handled in this action method.
	 *
	 * @uses Model_Question::vote() or Model_Answer::vote()
	 */
	public function action_vote()
	{
		if (!empty($this->errors))	return;
			
		if (!$this->posted_data_valid())	return;

		if (($post = $this->get_voted_post()) === NULL)	return;

		if ($this->user_is_post_owner($post))	return;
			
		$result = $this->vote_post($post);
		
		if ($result === FALSE)	return;

		$this->prepare_vote_result_response($result);
	}

	/**
	 * Accept / Undo accept an answer action
	 *
	 * @uses Model_Answer::accept_post()
	 */
	public function action_accept()
	{
		if (!empty($this->errors))	return;
			
		if (!$this->posted_data_valid())	return;

		if (($post = $this->get_accepted_post()) === NULL)	return;
		
		if ($this->user_is_post_owner($post))	return;
		
		$result = $this->accept_post($post);
		
		if ($result === FALSE)	return;

		$this->prepare_accept_result_response($result);
	}
	
	/***** PRIVATE METHODS *****/
	
	/**
	 * Checks if the posted data is valid
	 * 
	 * @return boolean
	 */
	private function posted_data_valid()
	{
		if (!$_POST || !isset($_POST['post_id']) || !is_numeric($_POST['post_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Gets the post that will be voted
	 * 
	 * @return mixed
	 */
	private function get_voted_post()
	{
		$post_id = (int) $_POST['post_id'];

		$post_type = (isset($_POST['post_type']) && $_POST['post_type'] === 'A')
			? Helper_PostType::ANSWER
			: Helper_PostType::QUESTION;

		try {
			if (($post = PostFactory::generate_post($post_type)->get($post_id)) === NULL)
			{
				$this->prepare_error_response(__('Post is not available'));
			}
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, "Vote::get_voted_post Could not fetch the post by ID: $post_id");
			$this->prepare_error_response(__('An Error Occured'));
		}
			
		return $post;
	}
	
	/**
	 * Checks if user and post owner is the same person or not
	 * 
	 * @param  object post
	 * @return boolean
	 */
	private function user_is_post_owner($post)
	{
		$user = Auth::instance()->get_user();
		if ($post->user_id === $user->id)
		{
			$this->prepare_error_response(__("It's your post."));
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Votes post
	 * 
	 * @param  object instance of Model_Post
	 * @return mixed, false on error
	 */
	private function vote_post($post)
	{
		try {
			$result = $post->vote((int) $_POST['vote_type']);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Vote | ' . $ex->getMessage());
				
			$this->prepare_error_response(__('Error Occured'));
			return FALSE;
		}
		
		return $result;
	}
	
	/**
	 * Prepares response after voting action done
	 * 
	 * @param int result
	 */
	private function prepare_vote_result_response($result)
	{
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
	 * Gets the post that will be accepted
	 * 
	 * @return object
	 */
	private function get_accepted_post()
	{
		$post_id = (int) $_POST['post_id'];

		if (($post = Model_Answer::get($post_id)) === NULL)
		{
			$this->prepare_error_response(__('Answer is not available'));
		}
		
		return $post;
	}
	
	/**
	 * Accepts post
	 * 
	 * @param  object instance of Model_Answer
	 * @return mixed, false on error
	 */
	private function accept_post(Model_Answer $post)
	{
		try {
			$result = $post->accept_post();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Accept Post | ' . $ex->getMessage());
				
			$this->prepare_error_response(__('Error Occured'));
			return FALSE;
		}

		return $result;
	}
	
	/**
	 * Prepares response after accept post action called
	 * 
	 * @param int result
	 */
	private function prepare_accept_result_response($result)
	{
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
