<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Check Class
 *
 * @package   qamini
 * @since     0.4.0
 * @author    Serdar Yildirim
 */
class Check
{
	/**
	 * Checks if value is null
	 * 
	 * @param  mixed $val
	 * @return boolean
	 */
	public static function isNull($val)
	{
		return $val === NULL;
	}
	
	/**
	 * Checks if it value not null
	 * 
	 * @param  mixed $val
	 * @return boolean
	 */
	public static function isNotNull($val)
	{
		return $val !== NULL;
	}
	
	/**
	 * Checks if string is empty
	 * 
	 * @param  string $str
	 * @return boolean
	 */
	public static function isStringEmpty($str)
	{
		return $str === '';
	}
	
	/**
	 * Checks if string is empty or null
	 * 
	 * @param  string $str
	 * @return boolean
	 */
	public static function isStringEmptyOrNull($str)
	{
		return $str === NULL || $str === '';
	}
	
	/**
	 * Checks if list is empty
	 * 
	 * @param  array $arr
	 * @return boolean
	 */
	public static function isListEmpty(Array &$arr)
	{
		return count($arr) < 1;
	}
	
	/**
	 * Checks if list is empty or null
	 * 
	 * @param  array $arr
	 * @return boolean
	 */
	public static function isListEmptyOrNull(Array &$arr)
	{
		return $arr === NULL || count($arr) < 1;
	}
	
	/**
	 * Checks if variable false or null
	 * 
	 * @param  object
	 * @return boolean
	 */
	public static function isNullOrFalse($val)
	{
		return $val === NULL || $val === FALSE;
	}
}