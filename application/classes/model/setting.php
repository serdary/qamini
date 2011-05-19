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
	
	private $_cache;
	
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
		$this->_cache = Cache::instance(Kohana::config('config.cache_driver'));

		if ($this->loaded_from_cache())	return;

		if (!$this->loaded_from_db())	$this->load_from_config();

		if (empty($this->_settings))
		{
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Settings could not be loaded from DB and settings config file!');
			return;
		}

		$this->set_cache();
	}
	
	/**
	 * Sets settings cache
	 */
	private function set_cache()
	{
		try {
			if ($this->_settings)
				$this->_cache->set('settings', $this->_settings, (int) Kohana::config('config.cache_ttl'));	
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'Model_Setting::load_settings, ex: ' . $ex->getMessage());
		}
	}
	
	/**
	 * Tries to load settings from cache
	 * 
	 * @return boolean
	 */
	private function loaded_from_cache()
	{
		return ($this->_settings = $this->_cache->get('settings'));
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
}