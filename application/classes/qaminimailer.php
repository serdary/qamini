<?php
/**
 * Qamini Mailer
 *
 * @package   qamini
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class QaminiMailer {

	private $_receiver_mail;
	private $_receiver_name;
	private $_mail_subject;
	private $_mail_from;
	private $_view_file;
	private $_view_vars;
	private $_email_config;
	
	public function __construct($receiver_mail, $receiver_name, $mail_subject, $mail_from, $view_file, $view_vars)
	{
		$this->_receiver_mail = $receiver_mail;
		$this->_receiver_name = $receiver_name;
		$this->_mail_subject = $mail_subject;
		$this->_mail_from = $mail_from;
		$this->_view_file = $view_file;
		$this->_view_vars = $view_vars;
	}
	
	/**
	 * Sends an email
	 */
	public function send()
	{
		if (!$this->email_valid())	return;
			
		// Load Swift Mailer required files
		require_once Kohana::find_file('vendor', 'swiftmailer/lib/swift_required');

		$message = $this->create_mail_message();

		$transport = $this->create_smpt_transport();

		// Try to send the email
		try {
			Swift_Mailer::newInstance($transport)->send($message);
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'QaminiMailer::send_mail(), view file: ' 
				. $this->_view_file . ' msg: '. $ex->getMessage());
		}
	}
	
	/**
	 * Checks given email whether it is valid or not
	 * 
	 * @return boolean
	 */
	private function email_valid()
	{
		return filter_var(filter_var($this->_receiver_mail, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * Creates a new swift mail object
	 * 
	 * @return object
	 */
	private function create_mail_message()
	{
		return Swift_Message::newInstance()
			->setSubject($this->_receiver_mail)
			->setFrom(array(Kohana::config('config.email') => $this->_receiver_mail))
			->setTo(array($this->_receiver_mail => $this->_receiver_mail))
			->setBody($this->create_mail_body());
	}
	
	/**
	 * Creates a new swift transport object
	 * 
	 * @return object
	 */
	private function create_smpt_transport()
	{
		// Get the email configuration into array
		$email_config = Kohana::config('email');
		
		return Swift_SmtpTransport::newInstance($email_config->server, $email_config->port, $email_config->security)
			->setUsername($email_config->username)
			->setPassword($email_config->password);
	}
	
	/**
	 * Creates mail body from a view file
	 * 
	 * @return string
	 */
	private function create_mail_body()
	{
		return View::factory('themes/' . Model_Setting::instance()->get('active_theme') . '/email/'
			. $this->_view_file, $this->_view_vars);
	}
}