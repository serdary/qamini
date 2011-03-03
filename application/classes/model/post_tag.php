<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Qamini Tag Model
 *
 * @package   qamini
 * @uses      Extends ORM
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Model_Post_Tag extends ORM {

	protected $_belongs_to = array('post' => array(), 'tag' => array());

}