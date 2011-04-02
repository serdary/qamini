<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Post Factory
 *
 * @package   qamini
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class PostFactory {

	public static function generate_post($post_type)
	{
		switch ($post_type)
		{
			case Model_Post::ANSWER:
				return new Model_Answer;
			case Model_Post::COMMENT:
				return new Model_Comment;
			default:
				return new Model_Question;
		}
	}
	
	public function get($post_id)
	{
		return self::get($post_id);
	}
}