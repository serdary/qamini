<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Errors Controller for all error pages, 404, 403, 500 etc.
 *
 * @package   qamini
 * @uses      Extends Controller_Template_Main
 * @since     0.2.0
 * @author    Serdar Yildirim
 */
class Controller_Errors extends Controller_Template_Main {

	/**
	 * The error message that will be displayed to the visitor
	 *
	 * @var string
	 */
	protected $_error_message;

	/**
	 * Checks if the current request is internal. If not, displays a 404 page,
	 * otherwise displays 500 error page.
	 */
	public function before()
	{
		parent::before();

		// Internal request only!
		if (Request::$initial === Request::$current)
		{
			$this->request->action(404);
		}

		$this->response->status((int) $this->request->action());
	}

	/**
	 * Displays 404 File Not Found page
	 */
	public function action_404()
	{
		$this->_error_message = _("Sorry the page you are looking for is not on this server.");

		$this->template->content = View::factory($this->get_theme_directory() . 'errors/404')
			->set('error_message', $this->_error_message);

		$this->template->title            = 'Oops, Page not found.';
		$this->template->meta_keywords    = '404, page not found';
		$this->template->meta_description = 'Page not found in this server.';
	}

	/**
	 * Displays 500 server error page
	 */
	public function action_500()
	{
		$this->_error_message = _("Looks like an error. We are working on that..");

		$this->template->content = View::factory($this->get_theme_directory() . 'errors/500')
			->set('error_message', $this->_error_message);

		$this->template->title            = 'Oops, Looks like a server error. We are working on that!';
		$this->template->meta_keywords    = '500, server error';
		$this->template->meta_description = 'Server error occured. Please try again later.';
	}
}