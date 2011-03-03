<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Setting Model.
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Model_Setting extends ORM {

	/**
	 * Holds settings fetched from DB
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Singleton instance for Model_Setting class
	 * 
	 * @var
	 */
	private static $instance;

	/**
	 * Returns the singleton of Model_Setting class
	 *
	 * @return object Instance of Model_Setting
	 */
	public static function instance()
	{
		if (self::$instance !== NULL)
			return self::$instance;

		return self::$instance = new self;
	}

	/**
	 * Loads all settings values from DB or setting config file.
	 */
	public function load_settings()
	{
		$cache = Cache::instance(Kohana::config('config.cache_driver'));

		// Try to get settings from cache
		if ($this->settings = $cache->get('settings'))
			return;

		$this->settings = array();
			
		$data = ORM::factory('setting')->where('setting_status', '=', 'active')->find_all();

		foreach($data as $setting)
		{
			$this->settings[$setting->key] = $setting->value;
		}

		// If settings could not be loaded from db, try to load from config file
		if (empty($this->settings))
		{
			$this->settings = Kohana::config('settings');
		}

		if (empty($this->settings))
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Settings could not be loaded from DB and settings config file!');
			return;
		}

		// Set cache for 24 hours
		if ($this->settings)
		{
			$cache->set('settings', $this->settings, 3600 * 24);
		}
	}

	/**
	 * Returns setting value of the specified key.
	 * If not found, returns empty string
	 *
	 * @param   string key
	 * @return  string the value of the key
	 */
	public function get($key = '')
	{
		$value = '';
		if (!$key || $key === '')
			return $value;

		if (empty($this->settings))
		{
			$this->load_settings();
		}

		if (array_key_exists($key, $this->settings))
			$value = $this->settings[$key];

		return $value;
	}
}