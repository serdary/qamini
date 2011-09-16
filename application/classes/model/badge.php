<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Badge Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
class Model_Badge extends ORM {

	protected $_has_many = array('users' => array('model' => 'user', 'through' => 'userbadge'));
	
	/**
	 * Checks if current badge is applicable to the user.
	 * Process the badge if user achieve / lost that
	 * 
	 * @param  Model_User object $user
	 * @param  boolean    add / take back the badge
	 * @return array result
	 */
	public function process($user, $subtract)
	{
		$msg = $this->check_achievement_by_category($user);
		
		if ($msg === TRUE)
			$msg = $this->process_badge($user);
		else if ($subtract)
		{ 
			$undo_result = $this->undo_process_badge($user);
			if ($undo_result[0] != 2)
				$msg = $undo_result;
		}
		
		return $msg;
	}
	
	/**
	 * Checks achievement for the current badge for the user
	 * 
	 * @param  object user
	 * @return array  result
	 */
	private function check_achievement_by_category($user)
	{
		$remaining_msg = '';
		
		switch ($this->badge_category_id)
		{
			case Helper_BadgeCategory::SUPPORTER:
				return $this->check_achievement_supporter($user);
				
			case Helper_BadgeCategory::OTHER:
				return $this->check_achievement_other($user);
			
			case Helper_BadgeCategory::POST_COUNT:
				$count = $user->question_count + $user->answer_count + $user->comment_count;
				$remaining_msg = __('You need %s posts to achieve %s badge');
				break;
			
			case Helper_BadgeCategory::QUESTION_COUNT:
				$count = $user->question_count;
				$remaining_msg = __('You need %s questions to achieve %s badge');
				break;
			
			case Helper_BadgeCategory::ANSWER_COUNT:
				$count = $user->answer_count;
				$remaining_msg = __('You need %s answers to achieve %s badge');
				break;
			
			case Helper_BadgeCategory::COMMENT_COUNT:
				$count = $user->comment_count;
				$remaining_msg = __('You need %s comments to achieve %s badge');
				break;
		}
		
		if ($count >= $this->badge_achieve_quantity)	return TRUE;
		
		$diff = $this->badge_achieve_quantity - $count;
		return array(0, sprintf($remaining_msg, $diff, $this->badge_name));
	}
	
	/**
	 * Adds achieved badge, if the user hasn't already achieved that
	 * 
	 * @param object user
	 */
	private function process_badge($user)
	{
		if (! $user->has('badges', ORM::factory('badge', array('badge_type' => $this->badge_type))))
		{			
			// Adding relation could not used. Thats because we need created_at and updated_at
			// columns for future reference
			$ub = Model_UserBadge::create_userbadge($this->id, $user->id);
			$ub->save();
			
			Kohana_Log::instance()->add(Kohana_Log::INFO, sprintf('PROCESS_BADGE user_id: %d badge: -%s (%d)-' 
				, $user->id, $this->badge_name, $this->id));
			
			return array(1, $this->badge_name . __(' Achieved!'));
		}
		return array(2, __('Already achieved'));
	}
	
	/**
	 * Take the current badge if user has achieved that
	 * 
	 * @param  object user
	 * @return array
	 */
	private function undo_process_badge($user)
	{
		if (! $user->has('badges', ORM::factory('badge', array('badge_type' => $this->badge_type))))
			return array(2, __('User doesnt have'));

		$user->remove('badges', $this);
		
		Kohana_Log::instance()->add(Kohana_Log::INFO, sprintf('UNDO_PROCESS_BADGE user_id: %d badge: -%s (%d)-' 
			, $user->id, $this->badge_name, $this->id));

		return array(3, $this->badge_name . __(' Lost!'));
	}
	
	/**
	 * Checks the supporter badge category
	 * 
	 * @param  object user
	 * @return array
	 */
	private function check_achievement_supporter($user)
	{	
		$total = Model_Reputation::get_user_reputation_by_type($user->id, 
			array(Helper_ReputationType::ANSWER_VOTE_UP, Helper_ReputationType::QUESTION_VOTE_UP, Helper_ReputationType::ACCEPTED_ANSWER));
		
		if ($total >= $this->badge_achieve_quantity)	return TRUE;
		
		return array(0, sprintf(__('You need %s up votes to achieve %s badge')
			, $this->badge_achieve_quantity - $total, $this->badge_name));
	}
	
	/**
	 * Checks other badge category
	 * 
	 * @param  object user
	 * @return array
	 */
	private function check_achievement_other($user)
	{	
		$method = 'check_achievement_' . $this->badge_type;
		if (! method_exists($this, $method))	return '';
		
		return $this->$method($user);
	}
	
	/*
	 * CUSTOMIZED - OTHER CATEGORY BADGES
	 * 
	 * NOTE: every new badges which is in OTHER category, should have a specification method
	 * Otherwise no badge can be achieved
	 * */
	
	/**
	 * Checks Other Badge Category -- Reputation 10 Reached
	 * 
	 * @param  object user
	 * @return array
	 */
	private function check_achievement_rep_10_reached($user)
	{
		if ($user->reputation >= $this->badge_achieve_quantity)	return TRUE;
		
		return array(0, sprintf(__('You need %s reputation points to achieve %s badge')
			, $this->badge_achieve_quantity - $user->reputation, $this->badge_name));
	}
	
	/**
	 * Checks Other Badge Category -- Reputation 100 Reached
	 * 
	 * @param  object user
	 * @return array
	 */
	private function check_achievement_rep_100_reached($user)
	{
		if ($user->reputation >= $this->badge_achieve_quantity)	return TRUE;
		
		return array(0, sprintf(__('You need %s reputation points to achieve %s badge')
			, $this->badge_achieve_quantity - $user->reputation, $this->badge_name));
	}
}