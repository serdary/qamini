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

	public function before()
	{
		parent::before();
		
		if ($this->user === NULL || 
		!$this->user->has('roles', ORM::factory('role', array('name' => 'admin'))))
		{
			$this->request->redirect(Route::get('homepage')->uri());
		}
	}

	public function action_postmoderate()
	{
		if ($this->has_errors())	return;

		$post_id = $this->check_moderate_post();

		try {
			if (($post = Model_Post::get($post_id, FALSE)) === NULL)
				$this->prepare_error_response(__('Post is not available'));
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, "AdminAjax::action_postmoderate Could not fetch the post by ID: $post_id");
			$this->prepare_error_response(__('An Error Occured'));
		}

		if ($this->has_errors())	return;
		
		$result = $post->cms_moderate($_POST['moderationVal']);
		
		$this->response->body(json_encode(array('result' => $result
			, 'message' => ($result > 0) ? __('Moderation succeeded') : __('Moderation failed'))));
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
	 * Checks posted moderation post
	 * 
	 * @return int
	 */
	private function check_moderate_post()
	{
		if (!$_POST || !isset($_POST['hdn_post_id']) || !is_numeric($_POST['hdn_post_id']))
		{
			$this->prepare_error_response(__('Invalid Request'));
			return NULL;
		}

		return (int) $_POST['hdn_post_id'];
	}
}
