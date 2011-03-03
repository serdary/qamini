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

	/**
	 * Returns the tag by the slug
	 *
	 * @param string slug of the tag
	 */
	public function get_tag_by_slug($slug)
	{
		return $this->where('slug', '=', $slug)->find();
	}

	/**
	 * Returns total count of the tags
	 *
	 * @param  string status of the tags that will be count
	 * @return int
	 */
	public function count_tags($status = Helper_TagStatus::NORMAL)
	{
		return $this->where('tag_status', '=', $status)->count_all();
	}

	/**
	 * Returns total count of the questions under the tag
	 *
	 * @return int
	 */
	public function count_tag_questions()
	{
		return $this->posts->count_all();
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
		return $this->where('tag_status', '!=', Helper_TagStatus::DELETED)
			->and_where('tag_status', '!=', Helper_TagStatus::BANNED)
			->order_by('post_count', 'desc')
			->order_by('updated_at', 'desc')
			->limit($page_size)
			->offset($offset)
			->find_all();
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
		return $this->posts->limit($page_size)->offset($offset)->find_all();
	}
}