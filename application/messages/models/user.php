<?php
return array(
	'username' => array(
		'not_empty' => 'Username is required.',
		'min_length' => 'Username must be at least :param2 character long.',
		'max_length' => 'Username should be at most :param2 characters.',
		'regex' => 'Username is not well formatted.',
		'username_available' => 'Username is not available.',
	),
	'password' => array(
        'not_empty' => 'Password is required.',
	),
	'email' => array(
        'not_empty' => 'Email is required.',
        'min_length' => 'Email must be at least :param2 characters long.',
		'max_length' => 'Email should be at most :param2 characters.',
		'email' => 'Email is not well formatted.',
		'email_available' => 'Email is not available.',
		'is_email_registered' => 'Email is not registered.',
	),
);