<?php
/**
 * Qamini View Helper
 *
 * @package   qamini
 * @since     0.5.0
 * @author    Serdar Yildirim
 */
class Helper_View {
	
	/**
	 * Returns list of question urls for CMS pages
	 */
	public static function get_cms_question_url_list()
	{
		return self::get_cms_post_url_list(Helper_PostType::QUESTION);
	}	
	
	/**
	 * Returns list of answer urls for CMS pages
	 */
	public static function get_cms_answer_url_list()
	{
		return self::get_cms_post_url_list(Helper_PostType::ANSWER);
	}
	
	/**
	 * Returns list of comment urls for CMS pages
	 */
	public static function get_cms_comment_url_list()
	{
		return self::get_cms_post_url_list(Helper_PostType::COMMENT);
	}
	
	/**
	 * Returns list of user urls for CMS pages
	 */
	public static function get_cms_user_url_list()
	{
		$links = array();
		$links[] = self::get_cms_user_url(Helper_AccountStatus::NORMAL);
		$links[] = self::get_cms_user_url(Helper_AccountStatus::DISAPPROVED);
		$links[] = self::get_cms_user_url(Helper_AccountStatus::DELETED);
		$links[] = self::get_cms_user_url(Helper_AccountStatus::APPROVED);
		$links[] = self::get_cms_user_url(Helper_AccountStatus::IN_REVIEW);
		$links[] = self::get_cms_user_url(Helper_AccountStatus::SPAM);
		
		return $links;
	}
	
	/**
	 * Returns a prepared user url for CMS pages
	 * 
	 * @param string moderation type
	 */
	public static function get_cms_user_url($mod_type)
	{
		return HTML::anchor(Route::get('admin_user')->uri(
			array('directory' => 'admin', 'action' => 'index', 'controller' => 'user'
			, 'moderation' => $mod_type)), $mod_type . __(' Users')); 
	}
	
	/**
	 * Returns list of post urls for CMS pages
	 * 
	 * @param string post type
	 */
	public static function get_cms_post_url_list($post_type)
	{
		$links = array();
		$links[] = self::get_cms_post_url($post_type, Helper_PostModeration::NORMAL);
		$links[] = self::get_cms_post_url($post_type, Helper_PostModeration::DISAPPROVED);
		$links[] = self::get_cms_post_url($post_type, Helper_PostModeration::DELETED);
		$links[] = self::get_cms_post_url($post_type, Helper_PostModeration::APPROVED);
		$links[] = self::get_cms_post_url($post_type, Helper_PostModeration::IN_REVIEW);
		
		return $links;
	}
	
	/**
	 * Returns a prepared post url for CMS pages
	 * 
	 * @param string post type
	 * @param string moderation type
	 * @return string
	 */
	public static function get_cms_post_url($post_type, $mod_type)
	{
		return HTML::anchor(Route::get('admin_post')->uri(
			array('directory' => 'admin', 'action' => 'index', 'controller' => 'post'
			, 'type' => $post_type, 'moderation' => $mod_type))
			 , sprintf("%s %s", $mod_type, $post_type)); 
	}
	
	/**
	 * Returns value, if value is null/empty default value is returned
	 * 
	 * @param  string value
	 * @param  string default value
	 * @return string
	 */
	public static function get_value($value, $default_val = '')
	{
		return Check::isStringEmptyOrNull($value) ? $default_val : $value;
	}
}