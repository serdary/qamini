<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Admin Main Controller 
 *
 * @package   qamini
 * @uses      Extends Controller_Admin_Template
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Controller_Admin_Main extends Controller_Admin_Template {

	public function before()
	{
		parent::before();
	}

	public function action_index()
	{		
    	$this->template->content = View::factory($this->get_theme_directory() . 'admin/main/index')
    		->set('theme_dir', $this->get_theme_directory());
	}

	/***** PRIVATE METHODS *****/
}
