TRUNCATE TABLE `post_tag`;
TRUNCATE TABLE `posts`;
TRUNCATE TABLE `reputations`;
TRUNCATE TABLE `roles_users`;
TRUNCATE TABLE `roles`;
TRUNCATE TABLE `settings`;
TRUNCATE TABLE `tags`;
TRUNCATE TABLE `user_tokens`;
TRUNCATE TABLE `users`;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'login', 'Unconfirmed user, granted immediately when signin up.'),
(2, 'user', 'Confirmed user, granted after account confirmation via e-mail.'),
(3, 'admin', 'Administrative user, has access to everything.');


INSERT INTO `users` (`id`, `twitter_id`, `email`, `username`, `password`, `logins`, `last_login`, `website`, `latest_activity`, `last_ip`, `reputation`, `question_count`, `answer_count`, `account_status`) VALUES
(1, NULL, 'unit@qamini.com', 'unittest', '80278e21f466f02943b484337758565f8dab667f229320412af5fd9c6b5e5331', 1, 1297039687, '', 1297039687, '127.0.0.1', 0, 0, 0, 'normal');

INSERT INTO `roles_users` (`user_id`, `role_id`) VALUES (1, 1);

INSERT INTO `users` (`id`, `twitter_id`, `email`, `username`, `password`, `logins`, `last_login`, `website`, `latest_activity`, `last_ip`, `reputation`, `question_count`, `answer_count`, `account_status`) VALUES
(2, NULL, 'unit2@qamini.com', 'admin', '80278e21f466f02943b484337758565f8dab667f229320412af5fd9c6b5e5331', 1, 1297039687, '', 1297039687, '127.0.0.1', 0, 0, 0, 'normal');

INSERT INTO `roles_users` (`user_id`, `role_id`) VALUES (2, 1);
INSERT INTO `roles_users` (`user_id`, `role_id`) VALUES (2, 2);
INSERT INTO `roles_users` (`user_id`, `role_id`) VALUES (2, 3);

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES
(1, 'active_theme', 'default_theme', 1296987527, 1296987527, 'active'),
(2, 'static_files_dir', 'default_theme', 1296987527, 1296987527, 'active'),
(3, 'question_add', '5', 1296987527, 1296987527, 'active'),
(4, 'answer_add', '7', 1296987527, 1296987527, 'active'),
(5, 'comment_add', '3', 1296987527, 1296987527, 'active'),
(6, 'question_vote_up', '2', 1296987527, 1296987527, 'active'),
(7, 'own_question_voted_up', '1', 1296987527, 1296987527, 'active'),
(8, 'question_vote_down', '-1', 1296987527, 1296987527, 'active'),
(9, 'own_question_voted_down', '-2', 1296987527, 1296987527, 'active'),
(10, 'answer_vote_up', '2', 1296987527, 1296987527, 'active'),
(11, 'own_answer_voted_up', '4', 1296987527, 1296987527, 'active'),
(12, 'answer_vote_down', '-1', 1296987527, 1296987527, 'active'),
(13, 'own_answer_voted_down', '-2', 1296987527, 1296987527, 'active'),
(14, 'accepted_answer', '4', 1296987527, 1296987527, 'active'),
(15, 'own_accepted_answer', '12', 1296987527, 1296987527, 'active');