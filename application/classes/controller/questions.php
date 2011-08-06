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

	public function before()
	{
		parent::before();
	}

	/**
	 * Most recent + active questions are listed.
	 */
	public function action_index()
	{
		$this->display_questions('action_index');
	}

	/**
	 * Newest questions are listed.
	 */
	public function action_newest()
	{
		$this->display_questions('action_newest');
	}

	/**
	 * Unanswered questions are listed.
	 */
	public function action_unanswered()
	{
		$this->display_questions('action_unanswered');
	}

	/**
	 * Question Detail Page.
	 *
	 * @uses Model_Question::get()
	 * @uses Model_Question::load_answers_and_comments()
	 * @uses Model_Post::get_post_owner_info()
	 */
	public function action_detail()
	{
		$this->add_detail_page_styles();
		$this->add_detail_page_scripts();

		$current_answer = new Model_Answer;

		if (($question_id = $this->request->param('id', 0)) === 0)
			$this->request->redirect(Route::get('question')->uri());
		
		$this->template->content = $this->get_detail_page_view($question_id)
			->bind('post', $question)
			->bind('post_owner_info', $post_owner_info)
			->bind('current_answer', $current_answer)
			->bind('handled_post', $handled_post);

		if (($question = Model_Question::get($question_id)) === NULL)
		{
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}

		$this->handle_view_count_of_post($question);
		 
		$question->load_answers_and_comments();

		$post_owner_info = $question->get_post_owner_info();
		
		$this->set_detail_page_meta_texts($question);

		if (!$post = $_POST)	return;

		$handled_post = $this->add_new_answer($post, $current_answer, $question);
	}

	/**
	 * Add New Question Action.
	 *
	 * @uses Model_Question::add()
	 * @uses Model_Post::handle_submitted_post_data()
	 * @uses Model_Question::check_question_title()
	 */
	public function action_ask()
	{
		$this->add_ask_page_scripts();
		
		$notify_user = FALSE;

		$question = new Model_Question;

		$this->template->content = $this->get_ask_page_view()
			->bind('post', $question)
			->bind('tag_list', $tag_list)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);
			
		$this->set_ask_page_meta_texts();

		// If form is not submitted, show the add question form
		if (!$post = $_POST)	return;
		
		if (! $this->check_user_has_write_access(FALSE))
		{
			$this->request->redirect(Route::get('static')->uri(array('action' => 'join')));
		}

		$this->check_csrf_token(Arr::get($post, 'token', ''));

		$question->handle_submitted_post_data($post);
		$notify_user = $post['notify_user'] !== '0';

		$question->sanitize_post_content($post);
		
		$question->values($post);

		$errors = array();
		if (!$question->check_question_title($post, $errors))
		{
			$tag_list = $this->create_taglist_from_posted_data($post);
			return;
		}
		
		$add_result = $this->process_add_question($question, $post, $tag_list, $errors);
		
		if ($add_result === FALSE)	return;

		Kohana_Log::instance()->add(Kohana_Log::INFO
			, sprintf("Controller-Question Ask:: %d user added Q Id: %d, Q slug: %s", $this->user->id, $question->id, $question->slug));
		
		if ($question->id > 0)
			$this->request->redirect(Route::get('question')->uri(
				array('action'=>'detail', 'id' => $question->id, 'slug' => $question->slug)));
	}

	/**
	 * Edit Question Action. Only registered users can edit a post.
	 *
	 * @uses Model_Question::get_user_question_by_id()
	 * @uses Model_Question::edit()
	 * @uses Model_Question::check_question_title()
	 * @uses Model_Question::generate_tag_list()
	 */
	public function action_edit()
	{
		// If question id is not supplied or user is not logged in, redirect to the question list
		if (($question_id = $this->request->param('id', 0)) === 0 
			|| $this->check_user_has_write_access() === FALSE)
		{
			$this->request->redirect(Route::get('question')->uri());
		}
		
		$this->add_edit_page_scripts();
			
		$this->template->content = $this->get_edit_page_view()
			->bind('post', $question)
			->bind('tag_list', $tag_list)
			->bind('errors', $errors)
			->bind('notify_user', $notify_user);

		try {
			$question = Model_Question::get_user_question_by_id($question_id, $this->user);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Question Edit:: ' . $ex->getMessage());
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));
		}

		$tag_list = $question->generate_tag_list();
		$notify_user = $question->notify_email !== '0';
		$errors = array();
		
		$this->set_detail_page_meta_texts($question);

		if (!$post = $_POST)	return;

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		if (!$question->check_question_title($post, $errors))
		{
			$question->title = $post['title'];
			return;
		}

		$question->handle_submitted_post_data($post);
		
		$edit_result = $this->process_edit_question($question, $post, $errors);
		
		if ($edit_result === FALSE)	return;
		
		Kohana_Log::instance()->add(Kohana_Log::INFO
			, sprintf("Controller-Question Edit:: %d user edited Q Id: %d", $this->user->id, $question->id));

		$this->request->redirect(Route::get('question')->uri(
			array('action'=>'detail', 'id' => $question->id, 'slug' => $question->slug)));
	}

	/**
	 * Delete Question Action.
	 *
	 * @uses Model_Question::get_user_question_by_id()
	 * @uses Model_Question::delete()
	 */
	public function action_delete()
	{
		// If question id is not supplied or user is not logged in, redirect to the question list
		if (($question_id = Arr::get($_POST, 'id', 0)) === 0 || $this->check_user_has_write_access() === FALSE)
		{
			$this->request->redirect(Route::get('question')->uri());
		}

		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($_POST, 'token', ''));

		try {
			Model_Question::get_user_question_by_id($question_id, $this->user)->delete();
		}
		catch (Exception $ex) {
			$msg = 'Exception::Delete Question | Message: ' . $ex->getMessage();
			Kohana_Log::instance()->add(Kohana_Log::ERROR, $msg);

			Message::set(Message::ERROR, __('Oops. Something went wrong, please try again.'));
		}
		
		Kohana_Log::instance()->add(Kohana_Log::INFO
			, sprintf("Controller-Question Delete:: %d user deleted Q Id: %d", $this->user->id, $question_id));

		$this->request->redirect(Route::get('question')->uri());
	}

	/**
	 * Used to display search results
	 * 
	 * @uses Model_Question::search()
	 */
	public function action_search()
	{
		if (($query = Arr::get($_GET, 'query', '')) === '')
			$this->request->redirect(Route::get('question')->uri());
			
		$this->template->content = $this->get_search_page_view()
			->bind('posts', $posts)
			->bind('total_questions', $total_posts)
			->bind('pagination', $pagination);

		$pagination = $this->prepare_pagination_for_search(Model_Question::count_search_results($query));

		$posts = Model_Question::search($query, $pagination->items_per_page, $pagination->offset);
		
		$this->set_search_page_meta_texts($query);
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Gets questions according to requested action parameter.
	 * Displays them with question/index view
	 *
	 * @param string indicates which action is called this method
	 * @uses  Model_Question::get_questions()
	 */
	private function display_questions($requested_action)
	{
		$this->template->content = $this->get_display_questions_page_view()
			->bind('posts', $questions)
			->bind('total_questions', $total_questions)
			->bind('pagination', $pagination);

		$total_questions = $this->get_total_questions($requested_action);

		$pagination = $this->prepare_pagination_for_display($total_questions);

		$questions = $this->get_active_questions($requested_action, $pagination);
		
		$this->set_display_page_meta_texts($requested_action);
	}
	
	/**
	 * Add stylesheet files to the template for detail page
	 */
	private function add_detail_page_styles()
	{
		$this->add_style(array('detail'));
	}
	
	/**
	 * Add javascript files to the template for detail page
	 */
	private function add_detail_page_scripts()
	{
		$this->add_js(array('detail', 'tinymce', 'tinymce/jscripts/tiny_mce/tiny_mce'));
	}
	
	/**
	 * Returns view object for detail page
	 * 
	 * @param int question id
	 * @return object
	 */
	private function get_detail_page_view($question_id)
	{
		return View::factory($this->get_theme_directory() . 'question/detail')
			->set('user_id', $this->user->id)
			->set('id', $question_id)
			->set('user_logged_in', $this->auth->logged_in())
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token());
	}
	
	/**
	 * Add javascript files to the template for ask page
	 */
	private function add_ask_page_scripts()
	{
		$this->add_wysiwyg_editor_js();
	}

	/**
	 * Add javascript files to the template for edit page
	 */
	private function add_edit_page_scripts()
	{
		$this->add_wysiwyg_editor_js();
	}
	
	/**
	 * Returns view object for ask page
	 * 
	 * @return object
	 */
	private function get_ask_page_view()
	{
		return View::factory($this->get_theme_directory() . 'question/add')
			->set('user_logged_in', $this->auth->logged_in())
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token());
	}
	
	/**
	 * Returns view object for edit page
	 * 
	 * @return object
	 */
	private function get_edit_page_view()
	{
		return View::factory($this->get_theme_directory() . 'question/edit')
			->set('theme_dir', $this->get_theme_directory())
			->set('token', $this->get_csrf_token());
	}
	
	/**
	 * Returns view object for search page
	 * 
	 * @return object
	 */
	private function get_search_page_view()
	{			
		return View::factory($this->get_theme_directory() . 'question/index')
			->set('user_id', $this->user->id);
	}
	
	/**
	 * Returns view object for display questions page
	 * 
	 * @return object
	 */
	private function get_display_questions_page_view()
	{			
		return View::factory($this->get_theme_directory() . 'question/index')
			->set('user_id', $this->user->id);
	}

	/**
	 * Adds a new answer if the post is validated
	 *
	 * @param  array  answer data
	 * @param  object reference of Model_Answer
	 * @param  object Model_Question
	 * @uses   Model_Post::handle_submitted_post_data()
	 * @return array
	 */
	private function add_new_answer($post, &$answer, $question)
	{
		if (! $this->check_user_has_write_access(FALSE))
		{
			/*$this->request->redirect(Route::get('question')->uri(
				array('action'=>'detail', 'id' => $question->id, 'slug' => $question->slug)));*/
			
			$this->request->redirect(Route::get('static')->uri(array('action' => 'join')));
		}
		
		// Check token to prevent csrf attacks, if token is not validated, redirect to question list
		$this->check_csrf_token(Arr::get($post, 'token', ''));

		$answer->sanitize_post_content($post);
		
		$answer->handle_submitted_post_data($post);

		$add_answer_result = $this->process_add_answer($post, $answer, $question);

		if ($add_answer_result === TRUE)
		{
			Kohana_Log::instance()->add(Kohana_Log::INFO
				, sprintf("Controller-Question Add Answer:: %d user added A ID: %d for Q Id: %d", $this->user->id, $answer->id, $question->id));
		
			$this->request->redirect(Route::get('question')->uri(
				array('action'=>'detail', 'id' => $question->id, 'slug' => $question->slug)));
		}
		
		return $add_answer_result;
	}
	
	/**
	 * Processes adding answer action. Returns error list if any, otherwiser returns true.
	 * 
	 * @param  array  answer data
	 * @param  object reference of Model_Answer
	 * @param  object Model_Question
	 * @uses   Model_Answer::insert()
	 * @return mixed
	 */
	private function process_add_answer($post, &$answer, $question)
	{
		$handled_post = array('errors' => array(),
                     'notify_user' => (isset($post['notify_user']) && $post['notify_user'] !== '0'));
		
		$answer->values($post);

		try {
			$add_answer_result = $answer->insert($post, $question->id);
		}
		catch (ORM_Validation_Exception $ex) {
			$handled_post['errors'] += array('answer_add_error' => $ex->errors('models'));
			return $handled_post;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Answer | Message: ' . $ex->getMessage());
				
			$handled_post['errors'] += array('answer_add_error' => __('Answer could not be added.'));
			return $handled_post;
		}
		
		return TRUE;
	}
	
	/**
	 * Creates taglist for the question.
	 * 
	 * @param  array posted data
	 * @return string
	 */
	private function create_taglist_from_posted_data($post)
	{
		return (isset($post['tags']) && $post['tags'] !== '') ? $post['tags'] : '';
	}
	
	/**
	 * Do process for adding question. Inserts it to the DB, returns false on error
	 * 
	 * @param  object reference of Model_Question
	 * @param  array data
	 * @param  string reference for tag list
	 * @param  reference for errors array
	 * @return boolean
	 */
	private function process_add_question(&$question, $post, &$tag_list, &$errors)
	{
		try {
			$add_result = $question->insert($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('question_add_error' => $ex->errors('models'));
			$tag_list = $this->create_taglist_from_posted_data($post);
			return FALSE;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Add Question | Message: ' . $ex->getMessage());
				
			$errors += array('question_add_error' => __('Question could not be added.'));
			$tag_list = $this->create_taglist_from_posted_data($post);
			return FALSE;
		}
		
		return $add_result;
	}
	
	/**
	 * Do process for editing question. Updates it on the DB.
	 * 
	 * @param  object reference of Model_Question
	 * @param  array data
	 * @param  reference for errors array
	 * @return boolean
	 */
	private function process_edit_question(&$question, $post, &$errors)
	{
		try {
			$edit_result = $question->edit($post);
		}
		catch (ORM_Validation_Exception $ex)
		{
			$errors += array('question_edit_error' => $ex->errors('models'));
			return FALSE;
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Exception::Edit Question | Message: ' . $ex->getMessage());
				
			$errors += array('question_edit_error' => __('Question could not be updated.'));
			return FALSE;
		}

		return $edit_result;
	}
	
	/**
	 * Prepares pagination control for search page
	 * 
	 * @param  int total post count
	 * @return object
	 */
	private function prepare_pagination_for_search($total_posts)
	{
		return Pagination::factory(array(
			'total_items' => $total_posts,
			'items_per_page' => Kohana::config('config.default_search_page_size'),
		));
	}
	
	/**
	 * Returns total question count
	 * 
	 * @param string request action
	 * @return int
	 */
	private function get_total_questions($requested_action)
	{
		switch ($requested_action)
		{
			case 'action_unanswered':
				return ORM::factory('post')->count_unanswered_posts(Helper_PostType::QUESTION);
			case 'action_index':
			case 'action_newest':
			default:
				return ORM::factory('post')->count_all_posts(Helper_PostType::QUESTION);
		}
	}
	
	/**
	 * Prepares pagination control for displaying questions page
	 * 
	 * @param  int total post count
	 * @return object
	 */
	private function prepare_pagination_for_display($total_questions)
	{
		return Pagination::factory(array(
			'total_items' => $total_questions,
			'items_per_page' => Kohana::config('config.default_questions_page_size'),
		));
	}
	
	/**
	 * Gets active questions to display
	 * 
	 * @param  string request action
	 * @param  objecy instance of Pagination
	 * @return array
	 */
	private function get_active_questions($requested_action, $pagination)
	{
		switch ($requested_action)
		{
			case 'action_index':
				return Model_Question::get_questions($pagination->items_per_page
					, $pagination->offset, Helper_PostStatus::ANSWERED);
			case 'action_unanswered':
				return Model_Question::get_questions($pagination->items_per_page
					, $pagination->offset, Helper_PostStatus::UNANSWERED);
			case 'action_newest':
			default:
				return Model_Question::get_questions($pagination->items_per_page
					, $pagination->offset);
		}
	}
	
	/**
	 * Sets template's meta fields for detail page
	 * 
	 * @param object Model_Question instance
	 */
	private function set_detail_page_meta_texts($question)
	{
		$this->prepare_metas($question->title, $question->content);
	}

	/**
	 * Sets template's meta fields for ask question page
	 */
	private function set_ask_page_meta_texts()
	{
		$this->prepare_metas(__('Ask a Question'), 'Ask a question on this question and answer website');
	}

	/**
	 * Sets template's meta fields for search page
	 */
	private function set_search_page_meta_texts($query)	{
		$this->prepare_metas($query . __(' Related Questions')
			, $query . 'related contents on this question and answer website');
	}
	
	/**
	 * Sets template's meta fields for display page
	 * 
	 * @param string request action
	 */
	private function set_display_page_meta_texts($requested_action)
	{
		switch ($requested_action)
		{
			case 'action_index':
				$title = __('Latest Answered Questions');
				$desc = __('Here is Latest Answered Questions on Q & A website.');
				break;
			case 'action_unanswered':
				$title = __('Unanswered Questions');
				$desc = __('Latest Unanswered Questions on Q & A website.');
				break;
			case 'action_newest':
			default:
				$title = __('Latest Questions');
				$desc = __('Here is Latest Questions on Q & A website.');
				break;
		}
		
		$this->prepare_metas($title, $desc);
	}
}
