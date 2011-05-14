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
	
	// Auto-update column for creation and update
	protected $_created_column = array('column' => 'created_at', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated_at', 'format' => TRUE);
	
	/**
	 * Holds settings fetched from DB
	 *
	 * @var array
	 */
	private $_settings = array();

	/**
	 * Singleton instance for Model_Setting class
	 * 
	 * @var
	 */
	private static $_instance;

	/**
	 * Returns the singleton of Model_Setting class
	 *
	 * @return object
	 */
	public static function instance()
	{
		if (self::$_instance !== NULL)
			return self::$_instance;

		return self::$_instance = new self;
	}

	/**
	 * Loads all settings values from DB or setting config file.
	 */
	public function load_settings()
	{
		$cache = Cache::instance(Kohana::config('config.cache_driver'));

		if ($this->loaded_from_cache($cache))	return;

		if (!$this->loaded_from_db())	$this->load_from_config();

		if (empty($this->_settings))
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Settings could not be loaded from DB and settings config file!');
			return;
		}

		// Set cache for 24 hours
		if ($this->_settings)
			$cache->set('settings', $this->_settings, 3600 * 24);
	}
	
	/**
	 * Tries to load settings from cache
	 * 
	 * @param object cache instance
	 * @return boolean
	 */
	private function loaded_from_cache($cache)
	{
		return ($this->_settings = $cache->get('settings'));
	}
	
	/**
	 * Tries to load settings from database
	 * 
	 * @return boolean
	 */	
	private function loaded_from_db()
	{
		$this->_settings = array();
			
		$data = ORM::factory('setting')->where('setting_status', '=', 'active')->find_all();

		$found = FALSE;
		
		foreach($data as $setting)
		{
			$this->_settings[$setting->key] = $setting->value;
			$found = TRUE;
		}
		
		return $found;
	}
	
	/**
	 * Tries to load settings from config file
	 */
	private function load_from_config()
	{
		$this->_settings = Kohana::config('settings');
	}

	/**
	 * Returns setting value of the specified key.
	 * If not found, returns empty string
	 *
	 * @param   string key
	 * @return  string
	 */
	public function get($key = '')
	{
		$value = '';
		if (!$key || $key === '')	return $value;

		if (empty($this->_settings))	$this->load_settings();

		if (array_key_exists($key, $this->_settings))	$value = $this->_settings[$key];

		return $value;
	}
	
	/* CMS Methods */
	
	/**
	 * Saves a new setting or update existing setting in DB 
	 * 
	 * @param int    setting id
	 * @param string new key
	 * @param string new value
	 */
	public static function cms_save_setting($setting_id, $new_key, $new_value)
	{
		if ($setting_id !== NULL)
			$setting = ORM::factory('setting', $setting_id);
		else
		{
			$setting = new Model_Setting;
			$setting->updated_at = time();
		}
		
		$setting->key = $new_key;
		$setting->value = $new_value;
		
		return $setting->save() ? TRUE : FALSE;
	}
}