<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Question Controller for listing, editing etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Questions extends Controller_Template_Main {

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Most recent + active questions are listed.
	 *
	 * @uses Controller_Questions::display_questions()
	 */
	public function action_index()
	{
		$this->display_questions('action_index');
	}

	/**
	 * Newest questions are listed.
	 *
	 * @uses Controller_Questions::display_questions()
	 */
	public function action_newest()
	{
		$this->display_questions('action_newest');
	}

	/**
	 * Unanswered questions are listed.
	 *
	 * @uses Controller_Questions::display_questions()
	 */
	public function action_unanswered()
	{
		$this->display_questions('action_unanswered');
	}

	/**
	 * Question Detail Page.
	 *
	 * @uses Model_Post::get()
	 * @uses Model_Post::get_answers_and_comments()
	 * @uses Model_Post::get_post_owner_info()
	 */
	public function action_detail()
	{
		$this->add_style(array('detail'));

		// Add detail javascript file for ajax operations on this page
		$this->add_js(array('detail'));

		$current_answer = ORM::factory('post');
		$comment = ORM::factory('post');

		// Holds user posted answer form, to re-fill the answer form in case of any error etc.
		$handled_post = array();

		if (($question_id = $this->request->param('id', 0)) === 0)
			$this->request->redirect(Route::get('question')->uri());
			
		$this->template->content = View::factory($this->get_theme_directory() . 'question/detail')
			->set('user_id', $this->user->id)
			->set('id', $question_id)
			->set('user_logged_in', $this->auth->logged_in())
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token())
			->bind('post', $question)
			->bind('post_owner_info', $post_owner_info)
			->bind('comment', $comment)
			->bind('current_answer', $current_answer)
			->bind('handled_post', $handled_post);
			
		if (($question = ORM::factory('post')->get($question_id)) === NULL)
		{
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}

		// Handle view counts of this question
		$this->handle_view_count_of_post($question);
		 
		// Get answers and comments of the question
		$question->get_answers_and_comments();

		$post_owner_info = $question->get_post_owner_info();

		// If form is not submitted return, if posted, try to add the answer
		if (!$post = $_POST)	return;

		$handled_post = $this->add_new_answer($post, $current_answer, $question);
	}

	/**
	 * Add New Question Action.
	 *
	 * @uses Model_Post::add_question()
	 * @uses Model_Post::handle_post_request()
	 * @uses Model_Post::check_question()
	 */
	public function action_ask()
	{
		// Holds errors
		$errors = array();
		$notify_user = FALSE;

		$question = ORM::factory('post');
			
		$this->template->content = View::factory($this->get_theme_directory() . 'question/add')
			->set('user_logged_in', $this->auth->logged_in())
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token())
			->bind('post', $question)
			->bind('tag_list', $tag_list)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);

		// If form is not submitted, show the add question form
		if (!$post = $_POST)	return;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		// If user just added the title, then show the full form filled with title
		if (isset($_POST['hdn_post_title']) && $_POST['hdn_post_title'] === '1')
		{
			$question->title = trim($_POST['title']);
			return;
		}

		// Do general process for posting model forms
		$question->handle_post_request($post);
		$notify_user = $post['notify_user'] !== '0';

		$question->values($post);

		if (!$question->check_question($post))
		{
			$errors += array('question_add_error' => __('Question title must be at least 10 characters long.'));
			$tag_list = (isset($post['tags']) && $post['tags'] !== '') ? $post['tags'] : '';
			return;
		}

		// Try to save the question
		try {
			$question_info = ORM::factory('post')->add_question($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('question_add_error' => $ex->errors('models'));
			$tag_list = (isset($post['tags']) && $post['tags'] !== '') ? $post['tags'] : '';
			return;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Question | Message: ' . $ex->getMessage());
				
			$errors += array('question_add_error' => __('Question could not be added.'));
			$tag_list = (isset($post['tags']) && $post['tags'] !== '') ? $post['tags'] : '';
			return;
		}

		if ($question_info['id'] > 0)
		{
			$this->request->redirect(Route::get('question')->uri(
				array('action'=>'detail', 'id' => $question_info['id'], 'slug' => $question_info['slug'])));
		}
	}

	/**
	 * Edit Question Action. Only registered users can edit a post.
	 *
	 * @uses Model_User::get_post_by_id()
	 * @uses Model_Post::edit_question()
	 * @uses Model_Post::generate_tag_list()
	 */
	public function action_edit()
	{
		// If question id is not supplied or user is not logged in, redirect to the question list
		if (($question_id = $this->request->param('id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		$question = ORM::factory('post');

		$this->template->content = View::factory($this->get_theme_directory() . 'question/edit')
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token())
			->bind('post', $question)
			->bind('tag_list', $tag_list)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);

		try {
			$question = $this->user->get_post_by_id($question_id);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Question Edit:: ' . $ex->getMessage());
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}

		$tag_list = $question->generate_tag_list();

		// Holds errors
		$errors = array();
		$notify_user = ($question->notify_email !== '0');

		// If form is not submitted, show the add question form
		if (!$post = $_POST)	return;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		if (!$question->check_question($post))
		{
			$question->title = $post['title'];
			$errors += array('question_add_error' => __('Question title must be at least 10 characters long.'));
			return;
		}

		// Do general process for posting model forms
		$question->handle_post_request($post);
			
		// Try to update the question
		try {
			$question_info = $question->edit_question($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('question_edit_error' => $ex->errors('models'));
			return;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Edit Question | Message: ' . $ex->getMessage());
				
			$errors += array('question_edit_error' => __('Question could not be updated.'));
			return;
		}

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $question_info['id'], 'slug' => $question_info['slug'])));
	}

	/**
	 * Delete Question Action.
	 *
	 * @uses Model_User::get_post_by_id()
	 * @uses Model_Post::delete_question()
	 */
	public function action_delete()
	{
		// If question id is not supplied or user is not logged in, redirect to the question list
		if (($question_id = Arr::get($_POST, 'id', 0)) === 0 || !$this->auth->logged_in())
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($_POST, 'token', ''));

		// Try to get and delete question
		try {
			$question = $this->user->get_post_by_id($question_id);
			$question->delete_question();
		}
		catch (Exception $ex) {
			$msg = 'Exception::Delete Question | Message: ' . $ex->getMessage();
			Kohana_Log::instance()->add(Kohana_Log::ERROR, $msg);

			Message::set(Message::ERROR, __('Oops. Something went wrong, please try again.'));
		}

		$this->request->redirect(Route::get('question')->uri());
	}

	/**
	 * Used to display search results
	 * 
	 * @uses Model_Post::search()
	 */
	public function action_search()
	{
		if (($query = Arr::get($_GET, 'query', '')) === '')
			$this->request->redirect(Route::get('question')->uri());

		$this->template->content = View::factory($this->get_theme_directory() . 'question/index')
			->set('user_id', $this->user->id)
			->bind('posts', $posts)
			->bind('total_questions', $total_posts)
			->bind('pagination', $pagination);

		// Get total posts count
		$total_posts = ORM::factory('post')->count_search_results($query);

		// Prepare pagination control
		$pagination = Pagination::factory(array(
			'total_items' => $total_posts,
			'items_per_page' => Kohana::config('config.default_search_page_size'),
		));

		// Get results
		$posts = ORM::factory('post')->search($query, $pagination->items_per_page, $pagination->offset);
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Gets questions according to requested action parameter.
	 * Displays them with question/index view
	 *
	 * @param string indicates which action is called this method
	 * @uses  Model_Post::count_posts()
	 * @uses  Model_Post::get_questions()
	 */
	private function display_questions($requested_action)
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'question/index')
			->set('user_id', $this->user->id)
			->bind('posts', $questions)
			->bind('total_questions', $total_questions)
			->bind('pagination', $pagination);

		// Get total questions count
		switch ($requested_action)
		{
			case 'action_unanswered':
					$total_questions = ORM::factory('post')->count_posts(Helper_PostType::QUESTION
						, Helper_PostStatus::UNANSWERED);
				break;
			case 'action_index':
			case 'action_newest':
			default:
					$total_questions = ORM::factory('post')->count_posts(Helper_PostType::QUESTION);
				break;
		}

		// Prepare pagination control
		$pagination = Pagination::factory(array(
			'total_items' => $total_questions,
			'items_per_page' => Kohana::config('config.default_questions_page_size'),
		));

		// Get active questions
		switch ($requested_action)
		{
			case 'action_index':
					$questions = ORM::factory('post')->get_questions($pagination->items_per_page
						, $pagination->offset, Helper_PostStatus::ANSWERED);
				break;
			case 'action_unanswered':
					$questions = ORM::factory('post')->get_questions($pagination->items_per_page
						, $pagination->offset, Helper_PostStatus::UNANSWERED);
				break;
			case 'action_newest':
			default:
					$questions = ORM::factory('post')->get_questions($pagination->items_per_page
						, $pagination->offset);
				break;
		}
	}

	/**
	 * Adds a new answer if the post is validated
	 *
	 * @param  array  Answer data
	 * @param  object reference of Model_Post
	 * @param  object Model_Post
	 * @uses   Model_Post::handle_post_request()
	 * @uses   Model_Post::add_answer()
	 * @return array
	 */
	private function add_new_answer($post, &$answer, $question)
	{
		$handled_post = array('errors' => array(),
                     'notify_user' => (isset($post['notify_user']) && $post['notify_user'] !== '0'));
			
		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		// Do general process for posting model forms
		$answer->handle_post_request($post);

		$answer->values($post);

		// Try to save the answer
		try {
			$add_answer_result = ORM::factory('post')->add_answer($post, $question->id);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$handled_post['errors'] += array('answer_add_error' => $ex->errors('models'));
			return $handled_post;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Answer | Message: ' . $ex->getMessage());
				
			$handled_post['errors'] += array('answer_add_error' => __('Answer could not be added.'));
			return $handled_post;
		}

		if ($add_answer_result)
		{
			$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $question->id, 'slug' => $question->slug)));
		}
	}
}
