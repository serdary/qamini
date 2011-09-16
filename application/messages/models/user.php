<?php
return array(
	'username' => array(
		'not_empty' => 'Username is required.',
		'min_length' => 'Username must be at least :param2 character long.',
		'max_length' => 'Username should be at most :param2 characters.',
		'regex' => 'Username is not well formatted.',
		'unique' => 'Username is not available.',
	),
	'password' => array(
        'not_empty' => 'Password is required.',
	),
	'password_confirm' => array(
        'matches' => 'The password fields did not match.',
	),
	'email' => array(
        'not_empty' => 'Email is required.',
        'min_length' => 'Email must be at least :param2 characters long.',
		'max_length' => 'Email should be at most :param2 characters.',
		'email' => 'Email is not well formatted.',
		'unique' => 'Email is not available.',
		'is_email_registered' => 'Email is not registered.',
	),
	'captcha' => array(
        'wrong' => 'Captch is not correct, please try again.',
	),
);