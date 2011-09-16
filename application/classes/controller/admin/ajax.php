<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini CMS Ajax Controller
 *
 * @package   qamini
 * @uses      Extends Controller_Basic_Ajax
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Ajax extends Controller_Basic_Ajax {

	/**
	 * Checks if current user has admin right
	 */
	public function before()
	{
		parent::before();
		
		if ($this->user === NULL || 
			!$this->user->has('roles', ORM::factory('role', array('name' => 'admin'))))
		{
			$this->prepare_error_response(__('Error Occured'));
			//$this->request->redirect(Route::get('homepage')->uri());
		}
	}

	/**
	 * Changes moderation type of a post
	 */
	public function action_postmoderate()
	{
		if ($this->has_errors())	return;

		$post_id = $this->check_moderate_data();
		if ($this->has_errors())	return;

		try {
			if (($post = Model_Post::get($post_id, FALSE)) === NULL)
				throw new Kohana_Exception('Post is not available');
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 
				"AdminAjax::action_postmoderate Could not fetch the post by ID: $post_id");
			
			$this->prepare_error_response(__('Post is not available'));
		}

		if ($this->has_errors())	return;
		
		$result = $post->cms_moderate($_POST['moderationVal']);
		
		$this->response->body(json_encode(array('result' => $result
			, 'message' => ($result > 0) ? __('Moderation succeeded') : __('Moderation failed'))));
	}

	/**
	 * Changes moderation type of a user
	 */
	public function action_usermoderate()
	{
		if ($this->has_errors())	return;

		$user_id = $this->check_moderate_data();
		if ($this->has_errors())	return;

		try {
			if (($user = Model_User::get($user_id, FALSE)) === NULL)
				$this->prepare_error_response(__('User is not available'));
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 
				"AdminAjax::action_usermoderate Could not fetch the user by ID: $user_id");
			
			$this->prepare_error_response(__('An Error Occured'));
		}

		if ($this->has_errors())	return;
		
		$result = $user->cms_moderate($_POST['moderationVal']);
		
		$this->response->body(json_encode(array('result' => $result
			, 'message' => ($result > 0) ? __('Moderation succeeded') : __('Moderation failed'))));
	}

	/**
	 * Deletes all posts of user or marks them as anonymous
	 */
	public function action_spammoderate()
	{
		if ($this->has_errors())	return;

		$this->check_spam_moderate_data();
		if ($this->has_errors())	return;
		
		$user_id = $_POST['hdn_id'];
		$delete_all_posts = $_POST['delete_all_posts'];
		$mark_anonymous = $_POST['mark_anonymous'];

		$moderation_succesful = TRUE;
		$message = __('Moderation is not successful');
			
		try {
			if (Check::isNull($user = Model_User::get($user_id, FALSE)))
				$this->prepare_error_response(__('User is not available'));

			$posts = $user->cms_get_user_posts();
			foreach ($posts as $post)
			{
				$result = 1;
				
				if ($delete_all_posts === 'true')
					$result = $post->cms_moderate(Helper_PostModeration::DISAPPROVED);
				else if ($mark_anonymous === 'true')
				{
					$post->cms_process_post_moderation_effects(Helper_PostModeration::APPROVED
					, Helper_PostModeration::DELETED, FALSE);
					
					$post->mark_post_anonymous();
				}
				else 
					$result = -1;
				
				if ($result < 1)
				{
					$moderation_succesful = FALSE;
					$message = __('Moderation is not successful for the post ') . $post->id;
					break;
				}
			}
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 
				'AdminAjax::action_spammoderate Could not moderate spams err: ' . $ex->getMessage());
			
			$this->prepare_error_response(__('An Error Occured'));
		}
			
		if ($moderation_succesful)	$message = __('Moderation succeeded');
				
		$this->response->body(json_encode(array('message' => $message)));
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
	 * Checks posted moderation post data
	 * 
	 * @return int
	 */
	private function check_moderate_data()
	{
		if (!$_POST || !isset($_POST['hdn_id']) || !is_numeric($_POST['hdn_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return NULL;
		}

		return (int) $_POST['hdn_id'];
	}

	/**
	 * Checks posted spam moderation data
	 */
	private function check_spam_moderate_data()
	{
		if (!$_POST || !isset($_POST['hdn_id']) || !is_numeric($_POST['hdn_id']) 
			|| !isset($_POST['delete_all_posts']) || !isset($_POST['mark_anonymous']))
			$this->prepare_error_response(__('Invalid Request'));
	}
}
