<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Tag Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Model_Tag extends ORM {

	// Auto-update column for create and update processes
	protected $_created_column = array('column' => 'created_at', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated_at', 'format' => TRUE);

	protected $_has_many = array('posts' => array('model' => 'post', 'through' => 'post_tag'));
   
	const NORMAL = 'normal';
	const DELETED = 'deleted';
	const BANNED = 'banned';

	/**
	 * Returns the tag by the slug
	 *
	 * @param string slug of the tag
	 * @return object
	 */
	public function get_tag_by_slug($slug)
	{
		return $this->where('slug', '=', $slug)->find();
	}
	
	/**
	 * Returns the tag by string
	 *
	 * @param  string tag
	 * @uses Model_Tag::get_tag_by_slug()
	 * @return object
	 */	
	public function get_tag($tag)
	{
		return $this->get_tag_by_slug(URL::title($tag));
	}

	/**
	 * Returns total count of the tags
	 *
	 * @param  string status of the tags that will be count
	 * @return int
	 */
	public function count_tags($status = Model_Tag::NORMAL)
	{
		return $this->where('tag_status', '=', $status)->count_all();
	}

	/**
	 * Returns tags according to page size and offset
	 *
	 * @param  int   page size
	 * @param  int   offset
	 * @return array Model_Tag objects
	 */
	public function get_tags($page_size, $offset)
	{
		return $this->where('tag_status', '!=', Model_Tag::DELETED)
			->and_where('tag_status', '!=', Model_Tag::BANNED)
			->order_by('post_count', 'desc')
			->order_by('updated_at', 'desc')
			->limit($page_size)
			->offset($offset)
			->find_all();
	}

	/**
	 * Returns total count of the questions under the tag
	 *
	 * @return int
	 */
	public function count_tag_questions()
	{
		return $this->posts->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->count_all();
	}

	/**
	 * Returns tags according to page size and offset
	 *
	 * @param  int   page size
	 * @param  int   offset
	 * @return array Model_Tag objects
	 */
	public function get_tag_questions($page_size, $offset)
	{
		return $this->posts->where('post_moderation', '!=', Helper_PostModeration::DELETED)
			->and_where('post_moderation', '!=', Helper_PostModeration::DISAPPROVED)
			->order_by('updated_at', 'DESC')
			->limit($page_size)->offset($offset)->find_all();
	}
	
	/**
	 * Tag process while adding to a new question
	 */
	public function process_add_to_new_question()
	{
		if ($this->is_banned())	return;
		
		$this->post_count++;
					
		if ($this->is_deleted())	$this->tag_status = Model_Tag::NORMAL;
	}
	
	/**
	 * Checks if the tag is banned
	 * 
	 * @return boolean
	 */
	public function is_banned()
	{
		return $this->tag_status === Model_Tag::BANNED;
	}
	
	/**
	 * Checks if the tag is deleted
	 * 
	 * @return boolean
	 */
	private function is_deleted()
	{
		return $this->tag_status === Model_Tag::DELETED;
	}
	
	/**
	 * Checks if the tag status is normal
	 * 
	 * @return boolean
	 */
	private function is_normal()
	{
		return $this->tag_status === Model_Tag::NORMAL;
	}
	
	/**
	 * Decreases post count while removing from a question
	 */
	public function decrease_post_count()
	{
		if (--$this->post_count === 0)
			$this->tag_status = Model_Tag::DELETED;
	}
	
	/**
	 * Creates and returns a new instance of tag
	 * 
	 * @param  string tag value
	 * @param  string created by
	 * @param  int timestamp
	 * @return object
	 */
	public static function create_object($tag, $created_by, $time)
	{
		$obj = new Model_Tag;
		$obj->value = $tag;
		$obj->slug = URL::title($tag);
		$obj->created_by = $created_by;
		$obj->updated_at = time();
		
		return $obj;
	}
}