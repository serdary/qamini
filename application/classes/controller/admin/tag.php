<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin Tag Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Admin_Template
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Tag extends Controller_Admin_Template {

	public function before()
	{
		parent::before();
	}

	public function action_index()
	{    		
		$this->template->content = View::factory($this->get_theme_directory() . 'admin/tag/index')
    		->set('theme_dir', $this->get_theme_directory())
    		->set('token', $this->get_csrf_token())
			->bind('tags', $tags)
			->bind('total_tags', $total_tags)
			->bind('pagination', $pagination);

		$total_tags = ORM::factory('tag')->count_tags();

		$pagination = Pagination::factory(array(
			'total_items' => $total_tags,
			'items_per_page' => Kohana::config('config.default_tags_page_size'),
		));
		
		$tags = ORM::factory('tag')->get_tags($pagination->items_per_page, $pagination->offset);
		
		if ((!$post = $_POST) || ! $this->check_tag_values()) 
			return;
		
		$tag = ORM::factory('tag', $post['tag_id']);
		$tag->value = $post['tag_value'];
		$tag->slug = $post['slug'];
		$tag->post_count = $post['post_count'];
		$tag->created_by = $post['created_by'];
		$tag->save();

		$this->request->redirect(Route::get('admin')->uri(
				array('directory' => 'admin', 'action' => 'index', 'controller' => 'tag')));
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Checks posted tag values
	 * 
	 * @return boolean
	 */
	private function check_tag_values()
	{
		if (!$_POST || !isset($_POST['tag_id']) || !is_numeric($_POST['tag_id']))
			return FALSE;

		if (Check::isStringEmptyOrNull($_POST['tag_value']) 
			|| Check::isStringEmptyOrNull($_POST['slug'])
			|| Check::isStringEmptyOrNull($_POST['post_count'])
			|| Check::isStringEmptyOrNull($_POST['created_by'])
			)
		 	return FALSE;

		return TRUE;
	}
}
