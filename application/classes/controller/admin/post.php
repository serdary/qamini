<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin Post Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Admin_Template
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Post extends Controller_Admin_Template {

	public function before()
	{
		parent::before();
	}

	public function action_index()
	{    		
		$this->template->content = View::factory($this->get_theme_directory() . 'admin/post/index')
    		->set('theme_dir', $this->get_theme_directory())
    		->set('token', $this->get_csrf_token())
			->bind('posts', $posts)
			->bind('total_posts', $total_posts)
			->bind('pagination', $pagination);
			
		$type = $this->request->param('type', Helper_PostType::QUESTION);
		$moderation = $this->request->param('moderation', Helper_PostModeration::NORMAL);

		$total_posts = ORM::factory('post')->cms_count_posts($type, $moderation);
		
		$pagination = Pagination::factory(array(
			'total_items' => $total_posts,
			'items_per_page' => Kohana::config('config.default_questions_page_size'),
		));

		$posts = Model_Question::cms_get_posts($pagination->items_per_page
			, $pagination->offset, $type, $moderation);
	}

	/***** PRIVATE METHODS *****/
}
