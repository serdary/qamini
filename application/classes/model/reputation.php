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

	const QUESTION_ADD = 'question_add';
	const ANSWER_ADD = 'answer_add';
	const COMMENT_ADD = 'comment_add';
	const QUESTION_VOTE_UP = 'question_vote_up';
	const OWN_QUESTION_VOTED_UP = 'own_question_voted_up';
	const QUESTION_VOTE_DOWN = 'question_vote_down';
	const OWN_QUESTION_VOTED_DOWN = 'own_question_voted_down';
	const ANSWER_VOTE_UP = 'answer_vote_up';
	const OWN_ANSWER_VOTED_UP = 'own_answer_voted_up';
	const ANSWER_VOTE_DOWN = 'answer_vote_down';
	const OWN_ANSWER_VOTED_DOWN = 'own_answer_voted_down';
	const ACCEPTED_ANSWER = 'accepted_answer';
	const OWN_ACCEPTED_ANSWER = 'own_accepted_answer';
	
	/**
	 * Creates a new reputation entry and returns
	 *
	 * @param  int user id
	 * @param  int post id
	 * @param  int reputation type
	 * @throws Kohana_Exception
	 * @return object
	 */
	public static function create_reputation($user_id, $post_id, $reputation_type)
	{
		$rep = new Model_Reputation;
		$rep->user_id = $user_id;
		$rep->post_id = $post_id;
		$rep->reputation_type = $reputation_type;
		$rep->updated_at = time();

		if (!$rep->save())
			throw new Kohana_Exception('New reputation could not be created');
			
		return $rep;
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
	 * @param  int user id
	 * @param  int post id
	 * @param  int reputation type
	 * @return object
	 */
	public function get_user_reputation_for_post($user_id, $post_id, $reputation_type)
	{
		return $this->where('user_id', '=', $user_id)
			->and_where('post_id', '=', $post_id)
			->and_where('reputation_type', '=', $reputation_type)
			->find();
	}
	
	/**
	 * Checks if any of the object is loaded in Model_Reputation objects array
	 * 
	 * @param  array Model_Reputation instances
	 * @return boolean
	 */
	public static function any_object_loaded($objects)
	{
		foreach ($objects as $obj)
			if ($obj->loaded())	return TRUE;
			
		return FALSE;
	}
	
	/**
	 * Returns owner's reputation type
	 * 
	 * @param  int reputation type
	 * @return string
	 */
	public static function get_owner_type($reputation_type)
	{
		switch ($reputation_type)
		{
			case Model_Reputation::QUESTION_VOTE_UP:
				return Model_Reputation::OWN_QUESTION_VOTED_UP;
			case Model_Reputation::QUESTION_VOTE_DOWN:
				return Model_Reputation::OWN_QUESTION_VOTED_DOWN;
			case Model_Reputation::ANSWER_VOTE_UP:
				return Model_Reputation::OWN_ANSWER_VOTED_UP;
			case Model_Reputation::ANSWER_VOTE_DOWN:
				return Model_Reputation::OWN_ANSWER_VOTED_DOWN;
			case Model_Reputation::ACCEPTED_ANSWER:
				return Model_Reputation::OWN_ACCEPTED_ANSWER;
		}
	}
}