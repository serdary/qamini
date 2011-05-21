<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Badge Service
 *
 * @package   qamini
 * @uses      Extends BaseService
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
class BadgeService extends BaseService
{
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
	
	public function __construct()
	{
		$this->cache_key = 'all_badges';
	}
	
	/**
	 * Tries to load badges from database
	 * 
	 * @return boolean
	 */	
	protected function load_from_db()
	{
		$this->items = array();
			
		$data = ORM::factory('badge')->where('badge_status', '=', 'active')->find_all();
		
		foreach($data as $badge)
		{
			$this->items[$badge->id] = $badge;
		}
	}
	
	/**
	 * Handles user badge after achieving / losing reputation
	 *
	 * @param  object user
	 * @param  string reputation type
	 * @param  bool true if rep. point will be decreased according to rep. type
	 */
	public function handle_badges($user, $reputation_type, $subtract)
	{
		$this->load_items();
		
		if (! $this->item_found)	return;
		
		$possible_badges = $this->get_possible_badges($reputation_type);
		
		Kohana_Log::instance()->add(Kohana_Log::INFO, '----------------------------------------');
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
		
		foreach ($this->items as $badge)
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