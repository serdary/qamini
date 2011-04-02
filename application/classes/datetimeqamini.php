<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini DateTime Class
 *
 * @package   qamini
 * @uses      Extends Kohana_Date
 * @since     0.3.0
 * @author    Serdar Yildirim
 */
class DateTimeQamini extends Kohana_Date
{		
	private $_time;
	private $_next_time;
	private $_diff;
	
	private $_template1;
	private $_template2;
	
	public function __construct($time)
	{
		$this->_time = $time;
		
		$this->_template1 = '%d %s ' . __('ago');
		$this->_template2 = '%d %s, %d %s ' . __('ago');
	}
	
	/**
	 * Gets relative difference between time and next time
	 * 
	 * @param int timestamp
	 * @return string
	 */
	public function get_relative_diff($next_time)
	{
		$this->_next_time = $next_time;
		
		if ($this->post_just_created())	return __('just now!');
		
		return $this->get_date_diff();
		
	}
	
 	/**
	 * Checks if the post just created or not
	 * 
	 * @return boolean
	 */
	private function post_just_created()
	{
		return ($this->_next_time - $this->_time) <= 1;
	}
	
	/**
	 * Gets relative difference strings
	 * 
	 * @return string
	 */
	private function get_date_diff()
	{
		$this->_diff = $this->calculate_diff();

		if ($this->date_diff_section_exist('years'))	return $this->create_date_diff('year', 'month');
		
		if ($this->date_diff_section_exist('months'))	return $this->create_date_diff('month', 'day');
		
		if ($this->date_diff_section_exist('days'))		return $this->create_date_diff('day', 'hour');
		
		if ($this->date_diff_section_exist('hours'))	return $this->create_date_diff('hour', 'minute');
		
		return $this->create_date_diff('minute', 'second');
	}

	/**
	 * Calculates difference between two timestamps
	 * 
	 * @return array
	 */
	private function calculate_diff()
	{		
		return Kohana_Date::span($this->_time, $this->_next_time);
	}
	
	/**
	 * Checks if that section exists in array, i.e: years, months
	 * 
	 * @param string section name
	 * @return boolean
	 */
	private function date_diff_section_exist($section)
	{
		return (isset($this->_diff[$section]) && $this->_diff[$section] > 0);
	}
	
	/**
	 * Creates relative difference string for two datetime sections, i.e: year, month
	 * 
	 * @param string section1
	 * @param string section1
	 * @return string
	 */
	private function create_date_diff($time_lbl1, $time_lbl2)
	{				
		$section1 = $time_lbl1 . 's';
		$section2 = $time_lbl2 . 's';
		
		if (!$this->date_diff_section_exist($section2))		
			return sprintf($this->_template1, $this->_diff[$section1], Inflector::plural($time_lbl1, $this->_diff[$section1]));
			
		return sprintf($this->_template2, $this->_diff[$section2], Inflector::plural($time_lbl2, $this->_diff[$section2])
					, $this->_diff[$section1], Inflector::plural($time_lbl1, $this->_diff[$section1]));
	}
}