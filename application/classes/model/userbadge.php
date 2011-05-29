<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini User Badge Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
class Model_UserBadge extends ORM {

	// Auto-update column for creation and update
	protected $_created_column = array('column' => 'created_at', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated_at', 'format' => TRUE);
	
	protected $_belongs_to = array('user' => array(), 'badge' => array());
	
	protected $_table_name = 'userbadge';
	
	/**
	 * Creates and returns a new user badge object
	 * 
	 * @param  int badge id
	 * @param  int user id
	 * @return object
	 */
	public static function create_userbadge($badge_id, $user_id)
	{
		$ub = new Model_UserBadge;
		$ub->user_id = $user_id;
		$ub->badge_id = $badge_id;
		$ub->updated_at = time();
		
		return $ub;
	}
	
	/**
	 * Gets the user badge object
	 * 
	 * @param  int badge id
	 * @param  int user id
	 * @return object
	 */
	public static function get_userbadge($badge_id, $user_id)
	{
		return ORM::factory('userbadge')->where('user_id', '=', $user_id)
			->and_where('badge_id', '=', $badge_id)
			->find();
	}
}