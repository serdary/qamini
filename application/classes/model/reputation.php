<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Reputation Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Model_Reputation extends ORM {

	// Auto-update column for create and update processes
	protected $_created_column = array('column' => 'created_at', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated_at', 'format' => TRUE);

	/**
	 * Creates a new reputation entry
	 *
	 * @param  int user id
	 * @param  int post id
	 * @param  int reputation type
	 * @throws Kohana_Exception
	 */
	public function create_reputation($user_id, $post_id, $reputation_type)
	{
		$this->user_id = $user_id;
		$this->post_id = $post_id;
		$this->reputation_type = $reputation_type;
		$this->updated_at = time();

		if (!$this->save())
			throw new Kohana_Exception('New reputation could not be created');
	}

	/**
	 * Deletes a reputation entry
	 *
	 * @param  int user id
	 * @param  int post id
	 * @param  int reputation type
	 * @throws Kohana_Exception
	 */
	public function delete_reputation($user_id, $post_id, $reputation_type)
	{
		$reputation = $this->get_user_reputation_for_post($user_id, $post_id, $reputation_type);

		if (!$reputation->loaded())
			throw new Kohana_Exception(sprintf('Reputation cannot be found with user_id: %d, post_id: %d, reputation_type: %s'
				, $user_id, $post_id, $reputation_type));

		$reputation->delete();
	}

	/**
	 * Gets a reputation by user id, post id and reputation type
	 *
	 * @param int user id
	 * @param int post id
	 * @param int reputation type
	 */
	public function get_user_reputation_for_post($user_id, $post_id, $reputation_type)
	{
		return $this->where('user_id', '=', $user_id)
			->and_where('post_id', '=', $post_id)
			->and_where('reputation_type', '=', $reputation_type)
			->find();
	}
}