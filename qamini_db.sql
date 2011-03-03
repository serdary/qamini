SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `qamini_development`
--

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_post_id` int(11) DEFAULT NULL,
  `title` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `post_status` enum('published','accepted','closed') CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL DEFAULT 'published',
  `post_moderation` enum('normal','disapproved','in_review','deleted','marked_anonymous') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `post_type` enum('question','answer','comment') COLLATE utf8_unicode_ci NOT NULL,
  `up_votes` int(7) unsigned NOT NULL DEFAULT '0',
  `down_votes` int(7) unsigned NOT NULL DEFAULT '0',
  `view_count` int(11) unsigned NOT NULL DEFAULT '1',
  `answer_count` int(11) NOT NULL DEFAULT '0',
  `comment_count` int(11) NOT NULL DEFAULT '0',
  `latest_activity` int(11) unsigned NOT NULL,
  `created_by` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notify_email` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(11) unsigned NOT NULL,
  `updated_at` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
CREATE TABLE IF NOT EXISTS `post_tag` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reputations`
--

DROP TABLE IF EXISTS `reputations`;
CREATE TABLE IF NOT EXISTS `reputations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reputation_type` varchar(100) CHARACTER SET latin1 NOT NULL,
  `created_at` int(11) unsigned NOT NULL,
  `updated_at` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'login', 'Unconfirmed user, granted immediately when signin up.'),
(2, 'user', 'Confirmed user, granted after account confirmation via e-mail.'),
(3, 'admin', 'Administrative user, has access to everything.');
-- --------------------------------------------------------

--
-- Table structure for table `roles_users`
--

DROP TABLE IF EXISTS `roles_users`;
CREATE TABLE IF NOT EXISTS `roles_users` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) CHARACTER SET utf8 NOT NULL,
  `value` varchar(300) CHARACTER SET utf8 NOT NULL,
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  `setting_status` enum('active','deleted','on_hold') CHARACTER SET utf8 NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=COMPACT;

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

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `post_count` int(11) unsigned NOT NULL DEFAULT '1',
  `created_by` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag_status` enum('normal','deleted','banned') CHARACTER SET latin1 NOT NULL DEFAULT 'normal',
  `created_at` int(11) unsigned NOT NULL,
  `updated_at` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `twitter_id` int(11) unsigned DEFAULT NULL,
  `email` varchar(127) CHARACTER SET utf8 DEFAULT NULL,
  `username` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `password` char(64) CHARACTER SET utf8 NOT NULL,
  `logins` int(11) unsigned NOT NULL,
  `last_login` int(11) unsigned DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `latest_activity` int(11) unsigned NOT NULL,
  `last_ip` varchar(50) CHARACTER SET utf8 NOT NULL,
  `reputation` int(11) NOT NULL,
  `question_count` int(6) unsigned NOT NULL,
  `answer_count` int(6) unsigned NOT NULL,
  `account_status` enum('normal','disapproved','deleted','spam','in_review') CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`),
  UNIQUE KEY `uniq_twitter_id` (`twitter_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `user_agent` varchar(40) NOT NULL,
  `token` varchar(40) NOT NULL,
  `type` varchar(100) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `expires` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token` (`token`),
  KEY `fk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `roles_users`
--
ALTER TABLE `roles_users`
  ADD CONSTRAINT `roles_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;