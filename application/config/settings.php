<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Settings Config File
 * Normally, this config values will not have any effect cause they should be loaded from DB.
 * If somehow, fetching database fails, these values will be used. 
 */
return array(

	/*
	 * Default theme for website
	 */
	'active_theme' => 'default_theme',

	/*
	 * Default directory name for static files
	 */
	'static_files_dir' => 'default_theme',

	/*
	 * Default user reputation values
	 */
	'question_add' => 5,
	'answer_add' => 7,
	'comment_add' => 3,
	'question_vote_up' => 2,
	'own_question_voted_up' => 1,
	'question_vote_down' => -1,
	'own_question_voted_down' => -2,
	'answer_vote_up' => 2,
	'own_answer_voted_up' => 4,
	'answer_vote_down' => -1,
	'own_answer_vote_down' => -2,
	'accepted_answer' => 4,
	'own_accepted_answer' => 12,

	/**
	 * Default site usage. 1 => visitors cannot add questions and answers, 0 => otherwise
	 */
	'login_required_to_add_content' => 1,
);