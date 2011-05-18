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
		
		$this->set_template_metas(new WebsiteMeta(__('About ') . Kohana::config('config.website_name')
			, __('About page of ') . Kohana::config('config.website_name') . ' question and answer website'));
	}

	/**
	 * Action to display help page of website
	 */
	public function action_help()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/help');
		
		$this->set_template_metas(new WebsiteMeta(__('Help ') . Kohana::config('config.website_name')
			, __('Help page of ') . Kohana::config('config.website_name') . ' question and answer website'));
	}

	/**
	 * Action to display contact page of website
	 */
	public function action_contact()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/contact');
		
		$this->set_template_metas(new WebsiteMeta(__('Contact ') . Kohana::config('config.website_name')
			, __('Contact page of ') . Kohana::config('config.website_name') . ' question and answer website'));
	}

	/**
	 * Action to display career page of website
	 */
	public function action_career()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/career');
	}

	/**
	 * Action to display call users to join to website 
	 */
	public function action_join()
	{
		$this->template->content = View::factory($this->get_theme_directory() . 'static/join');
	}
}