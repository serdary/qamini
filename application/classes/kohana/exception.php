<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Exception Controller to redirect user when an error occured
 *
 * @package   qamini
 * @uses      Extends Kohana_Kohana_Exception
 * @since     0.2.0
 * @author    Serdar Yildirim
 */
class Kohana_Exception extends Kohana_Kohana_Exception {

	public static function handler(Exception $e)
	{
		if (Kohana::DEVELOPMENT === Kohana::$environment)
		{
			parent::handler($e);
			return;
		}

		try
		{
			Kohana::$log->add(Log::ERROR, parent::text($e));

			$attributes = array
			(
                    'action'  => 500,
			);

			if ($e instanceof HTTP_Exception)
			{
				$attributes['action'] = $e->getCode();
			}

			// Error sub-request.
			echo Request::factory(Route::get('error')->uri($attributes))
			->execute()
			->send_headers()
			->body();
		}
		catch (Exception $ex)
		{
			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// Display the exception text
			echo parent::text($ex);

			// Exit with an error status
			exit(1);
		}
	}
}