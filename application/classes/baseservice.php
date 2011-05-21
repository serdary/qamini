<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Base Service
 *
 * @package   qamini
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
abstract class BaseService
{
	private $_cache;
	
	protected $item_found;
	
	protected $cache_key;
	
	/**
	 * Holds items fetched from DB
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Loads all items from DB
	 */
	public function load_items()
	{
		if (! Check::isListEmptyOrNull($this->items))	return;	
		
		$this->_cache = Cache::instance(Kohana::config('config.cache_driver'));

		$this->item_found = TRUE;
		
		if ($this->loaded_from_cache())	return;

		$this->load_from_db();

		if (empty($this->items))
		{
			Kohana_Log::instance()->add(Kohana_Log::INFO, 'Items could not be loaded from DB');
			$this->item_found = FALSE;
			return;
		}

		$this->set_cache();
	}
	
	/**
	 * Sets items cache
	 */
	private function set_cache()
	{
		try {
			if ($this->items)
				$this->_cache->set($this->cache_key, $this->items, (int) Kohana::config('config.cache_ttl'));	
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'BaseService::set_cache, ex: ' . $ex->getMessage());
		}
	}
	
	/**
	 * Tries to load items from cache
	 * 
	 * @return boolean
	 */
	private function loaded_from_cache()
	{
		return ($this->items = $this->_cache->get($this->cache_key));
	}
	
	abstract protected function load_from_db();

	/**
	 * Returns the item
	 *
	 * @param   int    item id
	 * @return  object
	 */
	public function get($id)
	{
		$this->load_items();
		
		if (! $this->item_found)	return;

		if (array_key_exists($id, $this->items))	return $this->items[$id];
	}
}