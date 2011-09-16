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
			case Helper_PostType::ANSWER:
				return new Model_Answer;
			case Helper_PostType::COMMENT:
				return new Model_Comment;
			case Helper_PostType::QUESTION:
				return new Model_Question;
			default:
				return new Model_Post;
		}
	}
	
	public function get($post_id)
	{ 
		return self::get($post_id);
	}
}