<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Tag Controller to show questions with a specific tag etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Tags extends Controller_Template_Main {

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Lists most active tags if a tag is not specified
	 * Otherwise lists the questions under that tag
	 *
	 * @uses Model_Tag::show_tag_list()
	 * @uses Model_Tag::show_questions_by_tag()
	 */
	public function action_index()
	{
		if (($tag_slug = $this->request->param('slug', '')) === '')
		{
			$this->add_style(array('tag'));
			$this->show_tag_list();
		}
		else
		{
			$this->show_questions_by_tag($tag_slug);
		}
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Lists all tags by updated time.
	 *
	 * @uses Model_Tag::count_tags()
	 * @uses Model_Tag::get_tags()
	 */
	private function show_tag_list()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'tag/index')
			->bind('tags', $tags)
			->bind('total_tags', $total_tags)
			->bind('pagination', $pagination);

		// Get total tags count
		$total_tags = ORM::factory('tag')->count_tags();

		// Prepare pagination control
		$pagination = Pagination::factory(array(
			'total_items' => $total_tags,
			'items_per_page' => Kohana::config('config.default_tags_page_size'),
		));

		// Get active questions
		$tags = ORM::factory('tag')->get_tags($pagination->items_per_page, $pagination->offset);
	}

	/**
	 * Lists all questions under the specified tag
	 *
	 * @param string requested tag
	 * @uses  Model_Tag::get_tag_by_slug()
	 * @uses  Model_Tag::count_tag_questions()
	 * @uses  Model_Tag::get_tag_questions()
	 */
	private function show_questions_by_tag($tag_slug)
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'question/index')
			->set('user_id', $this->user->id)
			->bind('posts', $questions)
			->bind('asked_by', $asked_by)
			->bind('total_questions', $total_questions)
			->bind('pagination', $pagination);
			
		$tag = ORM::factory('tag')->get_tag_by_slug($tag_slug);

		if ($tag->id === 0)
			$this->request->redirect(Route::get('error')->uri(array('action' => '404')));

		// Get total questions count
		$total_questions = $tag->count_tag_questions();

		// Prepare pagination control
		$pagination = Pagination::factory(array(
			'total_items' => $total_questions,
			'items_per_page' => Kohana::config('config.default_questions_page_size'),
		));

		// Get tag questions
		$questions = $tag->get_tag_questions($pagination->items_per_page, $pagination->offset);
	}
}
