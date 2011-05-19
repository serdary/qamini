<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Badge Service
 *
 * @package   qamini
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
class BadgeService
{
	private $_cache;
	
	private $_badge_found;
	
	/**
	 * Holds badges fetched from DB
	 *
	 * @var array
	 */
	private $_badges = array();

	/**
	 * Singleton instance for BadgeService
	 * 
	 * @var
	 */
	private static $_instance;

	/**
	 * Returns the singleton of BadgeService class
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
	 * Loads all badges from DB
	 */
	public function load_badges()
	{
		if (! Check::isListEmptyOrNull($this->_badges))	return;	
		
		$this->_cache = Cache::instance(Kohana::config('config.cache_driver'));

		$this->_badge_found = TRUE;
		
		if ($this->loaded_from_cache())	return;

		$this->load_from_db();

		if (empty($this->_badges))
		{
			Kohana_Log::instance()->add(Kohana_Log::INFO, 'Badges could not be loaded from DB');
			$this->_badge_found = FALSE;
			return;
		}

		$this->set_cache();
	}
	
	/**
	 * Sets badges cache
	 */
	private function set_cache()
	{
		try {
			if ($this->_badges)
				$this->_cache->set('badges', $this->_badges, (int) Kohana::config('config.cache_ttl_badges'));	
		}
		catch (Exception $ex) {
			Kohana_Log::instance()->add(Kohana_Log::ERROR, 'BadgeService::load_badges, ex: ' . $ex->getMessage());
		}
	}
	
	/**
	 * Tries to load badges from cache
	 * 
	 * @return boolean
	 */
	private function loaded_from_cache()
	{
		return ($this->_badges = $this->_cache->get('badges'));
	}
	
	/**
	 * Tries to load badges from database
	 * 
	 * @return boolean
	 */	
	private function load_from_db()
	{
		$this->_badges = array();
			
		$data = ORM::factory('badge')->where('badge_status', '=', 'active')->find_all();
		
		foreach($data as $badge)
		{
			$this->_badges[$badge->id] = $badge;
		}
	}

	/**
	 * Returns the badge
	 *
	 * @param   int    badge id
	 * @return  object
	 */
	public function get($id)
	{
		$this->load_badges();
		
		if (! $this->_badge_found)	return;

		if (array_key_exists($id, $this->_badges))	return $this->_badges[$id];
	}
	
	/**
	 * Handles a user badge after gaining / losing reputation
	 *
	 * @param  object user
	 * @param  string reputation type
	 * @param  bool true if rep. point will be decreased according to rep. type
	 */
	public function handle_badges($user, $reputation_type, $subtract)
	{
		$this->load_badges();
		
		if (! $this->_badge_found)	return;
		
		$possible_badges = $this->get_possible_badges($reputation_type);
		
		Kohana_Log::instance()->add(Kohana_Log::INFO, '-----------------------------------------------------------------');
		Kohana_Log::instance()->add(Kohana_Log::INFO, 'PB: ' . count($possible_badges) . ' RT: ' . $reputation_type);
		
		if (empty($possible_badges))	return;
		
		foreach ($possible_badges as $p)
			Kohana_Log::instance()->add(Kohana_Log::INFO, 'possible badges: ' . $p);
		
		$reputation_value = (int) Model_Setting::instance()->get($reputation_type);
		$subtract = ($reputation_value > 0) ? $subtract : !$subtract;
			
		$badge_result = array();
		foreach ($possible_badges as $badge)
		{
			$result = $badge->process($user, $subtract);
			
			if(! Check::isStringEmptyOrNull($result))
				$badge_result[] = $result;
		}
		
		foreach ($badge_result as $r)
		{
			Kohana_Log::instance()->add(Kohana_Log::INFO, 'result: ' . $r[0] . ' ---- ' . $r[1]);
			
			if ($r[0] === 1)
				Message::set(Message::NOTICE, $r[1] . "\n");
		}
	}
	
	/**
	 * Checks issued reputation type and returns a list of possible badges
	 *
	 * @param  string reputation type
	 * @return array
	 */
	private function get_possible_badges($reputation_type)
	{
		$badges = array();
		
		foreach ($this->_badges as $badge)
		{
			// Add badge if its category is "other", no need switch
			if ($badge->badge_category_id == Helper_BadgeCategory::OTHER)
				$badges[] = $badge;
			
			switch ($reputation_type)
			{
				case Model_Reputation::QUESTION_ADD:
					if ($badge->badge_category_id == Helper_BadgeCategory::QUESTION_COUNT
						|| $badge->badge_category_id == Helper_BadgeCategory::POST_COUNT)
					$badges[] = $badge;
					break;
				
				case Model_Reputation::ANSWER_ADD:
					if ($badge->badge_category_id == Helper_BadgeCategory::ANSWER_COUNT
						|| $badge->badge_category_id == Helper_BadgeCategory::POST_COUNT)
					$badges[] = $badge;
					break;
				
				case Model_Reputation::COMMENT_ADD:
					if ($badge->badge_category_id == Helper_BadgeCategory::COMMENT_COUNT
						|| $badge->badge_category_id == Helper_BadgeCategory::POST_COUNT)
					$badges[] = $badge;
					break;
					
				case Model_Reputation::ACCEPTED_ANSWER:
				case Model_Reputation::ANSWER_VOTE_UP:
				case Model_Reputation::QUESTION_VOTE_UP:
					if ($badge->badge_category_id == Helper_BadgeCategory::SUPPORTER)
						$badges[] = $badge;
					break;
			}
		}
		
		return $badges;
	}
}