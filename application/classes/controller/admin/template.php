<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin Template Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Template extends Controller_Template_Main {

	public function before()
	{
		parent::before();
	
		if ($this->user === NULL || 
			!$this->user->has('roles', ORM::factory('role', array('name' => 'admin'))))
			{
				$this->request->redirect(Route::get('homepage')->uri());
			}
			
		$this->add_style(array('cms'));
		
		$this->add_js(array('cms'));
	}	
}
