<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A simple "flash message" system.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Message {

	/**
	 * @var  string  default session key used for storing messages
	 */
	public static $session_key = 'message';

	// Message types
	const SUCCESS = 'success';
	const NOTICE  = 'notice';
	const ERROR   = 'error';

	/**
	 * Adds a new message.
	 *
	 * @param   string  message type (e.g. Message::SUCCESS)
	 * @param   string  message text
	 * @param   array   any options for the message
	 * @return  void
	 */
	public static function set($type, $text, array $options = NULL)
	{
		// Load existing messages
		$messages = (array) Message::get();

		// Add new message
		$messages[] = (object) array(
			'type'    => $type,
			'text'    => $text,
			'options' => (array) $options,
		);

		// Store the updated messages
		Session::instance()->set(Message::$session_key, $messages);
	}

	/**
	 * Returns all messages.
	 *
	 * @return  array or NULL
	 */
	public static function get()
	{
		return Session::instance()->get(Message::$session_key);
	}

	/**
	 * Clears all messages.
	 *
	 * @return  void
	 */
	public static function clear()
	{
		Session::instance()->delete(Message::$session_key);
	}

	/**
	 * Renders the message(s), and by default clears them too.
	 *
	 * @param   mixed    string of the view to use, or a Kohana_View object
	 * @param   boolean  set to FALSE to not clear messages
	 * @return  string   message output (HTML)
	 */
	public static function render($view = NULL, $clear = TRUE)
	{
		// Nothing to render
		if (($messages = Message::get()) === NULL)
			return '';

		// Clear all messages
		if ($clear)
		{
			Message::clear();
		}

		if ($view === NULL)
		{
			// Use the default view
			$view = 'message/basic';
		}

		if ( ! $view instanceof Kohana_View)
		{
			// Load the view file
			$view = View::factory($view);
		}

		// Return the rendered view
		return $view->set('messages', $messages)->render();
	}

}