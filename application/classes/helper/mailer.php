<?php
/**
 * Qamini Mailer
 *
 * @package   qamini
 * @since     0.2.0
 * @author    Serdar Yildirim
 * @deprecated
 */
class Helper_Mailer {
	
	/**
	 * Singleton instance for Helper_Mailer class
	 * 
	 * @var
	 */
	private static $instance;

	/**
	 * Returns the singleton instance of Helper_Mailer class
	 *
	 * @return object Instance of Helper_Mailer
	 */
	public static function instance()
	{
		if (self::$instance !== NULL)
			return self::$instance;

		return self::$instance = new self;
	}
	
	/**
	 * Sends an email
	 * 
	 * @param string receiver mail address
	 * @param string receiver name
	 * @param string mail subject
	 * @param string mail from field
	 * @param string view file name
	 * @param array view variables
	 */
	public function send_mail($receiver_mail, $receiver_name, $mail_subject, $mail_from, $view_file, $view_vars)
	{
		if (!filter_var(filter_var($receiver_mail, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL))
			return;
			
		// Load Swift Mailer required files
		require_once Kohana::find_file('vendor', 'swiftmailer/lib/swift_required');

		// Get the email configuration into array
		$email_config = Kohana::$config->load('email');

		$body = View::factory('themes/' . Model_Setting::instance()->get('active_theme') . '/email/'
			. $view_file, $view_vars);

		// Create an email message to reset user's password
		$message = Swift_Message::newInstance()
			->setSubject($mail_subject)
			->setFrom(array(Kohana::$config->load('config.email') => $mail_from))
			->setTo(array($receiver_mail => $receiver_name))
			->setBody($body);

		// Connect to the server
		$transport = Swift_SmtpTransport::newInstance($email_config->server, $email_config->port, $email_config->security)
			->setUsername($email_config->username)
			->setPassword($email_config->password);

		// Try to send the email
		try {
			Swift_Mailer::newInstance($transport)->send($message);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Helper_Mailer::send_mail(), view file: ' 
				. $view_file . ' msg: '. $ex->getMessage());
		}
	}
}