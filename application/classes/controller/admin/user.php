<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin User Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Admin_Template
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_User extends Controller_Admin_Template {

	public function before()
	{
		parent::before();
	}

	public function action_index()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'admin/user/index')
    		->set('theme_dir', $this->get_theme_directory())
    		->set('token', $this->get_csrf_token())
			->bind('users', $users)
			->bind('total_users', $total_users)
			->bind('pagination', $pagination);

		$status = $this->request->param('moderation', Helper_AccountStatus::NORMAL);

		$total_users = Model_User::cms_count_users($status);
		
		$pagination = Pagination::factory(array(
			'total_items' => $total_users,
			'items_per_page' => Kohana::config('config.default_users_page_size'),
		));
			
		$users = Model_User::cms_get_users($pagination->items_per_page, $pagination->offset, $status);
	}

	/***** PRIVATE METHODS *****/

	/**
	 * Checks posted setting values
	 * 
	 * @return boolean
	 */
	private function check_setting_values()
	{
		if (!$_POST || !isset($_POST['input_setting_key']) || !isset($_POST['input_setting_value']) 
		 || ((int) $_POST['hdn_new_setting'] != 1 && !is_numeric($_POST['hdn_setting_id'])))
			return FALSE;

		if (Check::isStringEmptyOrNull($_POST['input_setting_key']) 
			|| Check::isStringEmptyOrNull($_POST['input_setting_value']))
		 	return FALSE;

		return TRUE;
	}
}
