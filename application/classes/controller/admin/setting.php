<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin Settings Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Admin_Template
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Setting extends Controller_Admin_Template {

	public function before()
	{
		parent::before();
	}

	public function action_index()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'admin/setting/index')
    		->set('theme_dir', $this->get_theme_directory())
    		->set('token', $this->get_csrf_token())
			->bind('settings', $settings);

		$settings = ORM::factory('setting')->find_all();

		if ((!$post = $_POST) || !$this->check_setting_values()) 
			return;

		$new_key = $post['input_setting_key'];
		$new_value = $post['input_setting_value'];
		
		$setting_id = ((boolean) $post['hdn_new_setting']) ? NULL : $post['hdn_setting_id'];

		$valid = TRUE;
		if (Check::isNotNull($setting_id))
		{
			foreach($settings as $setting)
			{
				if ($setting->key === $new_key && $setting->id !== $setting_id)
					$valid = FALSE;
			}
		}
		
		if ($valid)
		{
			if ($setting_id !== NULL)
				$setting_obj = ORM::factory('setting', $setting_id);
			else
			{
				$setting_obj = new Model_Setting;
				$setting_obj->updated_at = time();
			}
			
			$setting_obj->key = $new_key;
			$setting_obj->value = $new_value;
			$setting_obj->save();	
		}
		
		$this->request->redirect(Route::get('admin')->uri(
				array('directory' => 'admin', 'action' => 'index', 'controller' => 'setting')));
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
			|| Check::isStringEmptyOrNull($_POST['input_setting_value'])
			)
		 	return FALSE;

		return TRUE;
	}
}
