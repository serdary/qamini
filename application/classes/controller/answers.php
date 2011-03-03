<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Answer Controller for adding new answers etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Answers extends Controller_Template_Main {

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Answer Edit Page. Only registered users can edit a post.
	 *
	 * @uses Model_User::get_post_by_id()
	 * @uses Model_Post::handle_post_request()
	 * @uses Model_Post::edit_answer()
	 */
	public function action_edit()
	{
		// If answer id and question id is not supplied or user is not logged in, redirect to the question list
		if (($answer_id = $this->request->param('id', 0)) === 0 ||
			($question_id = $this->request->param('question_id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		$answer = ORM::factory('post');

		$this->template->content = View::factory($this->get_theme_directory() . 'answer/edit')
			->set('question_id', $question_id)
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token())
			->bind('post', $answer)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);

		try {
			$answer = $this->user->get_post_by_id($answer_id, Helper_PostType::ANSWER);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Answer Edit:: ' . $ex->getMessage());
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}
		 
		// If answer's parent post id is not equal to requested question id, redirect user to question list
		if ($answer->parent_post_id !== $question_id)
			$this->request->redirect(Route::get('question')->uri());
		 
		// Holds errors
		$errors = array();
		$notify_user = ($answer->notify_email !== '0');
		 
		// If form is not submitted, show the edit answer form
		if (!$post = $_POST)	return;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		// Do general process for posting model forms
		$answer->handle_post_request($post);

		// Try to update the answer
		try {
			$question_slug = $answer->edit_answer($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('answer_edit_error' => $ex->errors('models'));
			return;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Edit Answer | Message: ' . $ex->getMessage());
				
			$errors += array('answer_edit_error' => __('Answer could not be updated.'));
			return;
		}

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $question_id, 'slug' => $question_slug)));
	}

	/**
	 * Delete Answer Action.
	 *
	 * @uses Model_User::get_post_by_id()
	 * @uses Model_Post::delete_answer()
	 */
	public function action_delete()
	{
		// If answer id and question id is not supplied or user is not logged in, redirect to the question list
		if (($answer_id = Arr::get($_POST, 'id', 0)) === 0 ||
			($question_id = Arr::get($_POST, 'parent_id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($_POST, 'token', ''));

		// Try to get and delete the answer
		try {
			$answer = $this->user->get_post_by_id($answer_id, Helper_PostType::ANSWER);

			$question_slug = $answer->delete_answer();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Delete Answer | Message: ' . $ex->getMessage());

			Message::set(Message::ERROR, __('Oops. Something went wrong, please try again.'));
		}

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $question_id, 'slug' => $question_slug)));
	}
}
