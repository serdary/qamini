<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Website Meta Class
 *
 * @package   qamini
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class WebsiteMeta
{
	private $_title;
	private $_description;
	private $_keywords;
	
	public function __construct($title, $desc, $keywords = NULL)
	{
		$this->_title = $title;
		$this->_description = $desc;
		$this->_keywords = ($keywords !== NULL) ? $keywords : __('q2a, question&answer');
	}
	
	/**
	 * Generates and return a default website meta object
	 * 
	 * @return object
	 */
	public static function generate_default_metas()
	{
		$meta = new WebsiteMeta(Kohana::config('config.website_name') . __(' Question & Answer System'), 
			__('Open source Question & answer website, powered by Kohana 3.1'));
			
		return $meta;
	}
	
	/**
	 * Getter of title
	 * 
	 * @return string
	 */
	public function get_title()
	{
		return $this->_title;
	}
	
	/**
	 * Getter of description
	 * 
	 * @return string
	 */
	public function get_description()
	{
		return $this->_description;
	}
	
	/**
	 * Getter of keywords
	 * 
	 * @return string
	 */
	public function get_keywords()
	{
		return $this->_keywords;
	}
}