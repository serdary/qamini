<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Static Controller for all static pages, e.g: about, homepage..
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Controller_Static extends Controller_Template_Main {

	/**
	 * Calls parent's before method
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Action to display homepage of website
	 */
	public function action_index()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/index');
	}

	/**
	 * Action to display about page of website
	 */
	public function action_about()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/about');
	}

	/**
	 * Action to display help page of website
	 */
	public function action_help()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/help');
	}

	/**
	 * Action to display contact page of website
	 */
	public function action_contact()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/contact');
	}

	/**
	 * Action to display career page of website
	 */
	public function action_career()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/career');
	}
}