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

	public function before()
	{
		parent::before();
	}

	/**
	 * Answer Edit Page. Only registered users can edit a post.
	 *
	 * @uses Model_Answer::get_user_answer_by_id()
	 * @uses Model_Post::handle_submitted_post_data()
	 * @uses Model_Answer::edit()
	 */
	public function action_edit()
	{
		// If answer id and question id is not supplied or user is not logged in, redirect to the question list
		if (($answer_id = $this->request->param('id', 0)) === 0 ||
			($question_id = $this->request->param('question_id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		$this->template->content = $this->get_edit_page_view($question_id)
			->bind('post', $answer)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);

		try {
			$answer = Model_Answer::get_user_answer_by_id($answer_id, $this->user);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Answer Edit:: ' . $ex->getMessage());
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}
		 
		// If answer's parent post id is not equal to requested question id, redirect user to question list
		if ($answer->parent_post_id !== $question_id)
			$this->request->redirect(Route::get('question')->uri());

		$errors = array();
		$notify_user = ($answer->notify_email !== '0');
		 
		// If form is not submitted, show the edit answer form
		if (!$post = $_POST)	return;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		$answer->handle_submitted_post_data($post);

		$parent_question = $this->process_edit_answer($answer, $post, $errors);
		
		if ($parent_question === FALSE)	return;

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $parent_question->id, 'slug' => $parent_question->slug)));
	}

	/**
	 * Delete Answer Action.
	 *
	 * @uses Model_Answer::get_user_answer_by_id()
	 * @uses Model_Answer::delete()
	 */
	public function action_delete()
	{
		// If answer id or question id is not supplied or user is not logged in, redirect to the question list
		if (($answer_id = Arr::get($_POST, 'id', 0)) === 0 ||
			($question_id = Arr::get($_POST, 'parent_id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($_POST, 'token', ''));

		$parent_question = $this->process_delete_answer($answer_id);
		
		if ($parent_question === NULL)	return;

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $parent_question->id, 'slug' => $parent_question->slug)));
	}

	/***** PRIVATE METHODS *****/
	
	/**
	 * Returns view object for edit page
	 * 
	 * @param int question id
	 * @return object
	 */
	private function get_edit_page_view($question_id)
	{			
		return View::factory($this->get_theme_directory() . 'answer/edit')
			->set('question_id', $question_id)
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token());
	}
	
	/**
	 * Do process for editing answer. Updates it on the DB
	 * 
	 * @param  object reference of Model_Answer
	 * @param  array data
	 * @param  reference for errors array
	 * @return mixed
	 */
	private function process_edit_answer(&$answer, $post, &$errors)
	{
		try {
			$parent_question = $answer->edit($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('answer_edit_error' => $ex->errors('models'));
			return FALSE;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Edit Answer | Message: ' . $ex->getMessage());
				
			$errors += array('answer_edit_error' => __('Answer could not be updated.'));
			return FALSE;
		}
		
		return $parent_question;
	}
	
	/**
	 * Do process for deleting an answer. Updates it on the DB
	 * 
	 * @param int answer id
	 * @return mixed
	 */
	private function process_delete_answer($answer_id)
	{
		try {
			$parent_question = Model_Answer::get_user_answer_by_id($answer_id, $this->user)->delete();
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Delete Answer | Message: ' . $ex->getMessage());

			Message::set(Message::ERROR, __('Oops. Something went wrong, please try again.'));
			
			return FALSE;
		}
		
		return $parent_question;
	}
}