SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `qamini_development`
--

-- --------------------------------------------------------

--
-- Drop ALL tables
--

DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `post_tag`;
DROP TABLE IF EXISTS `reputations`;
DROP TABLE IF EXISTS `roles_users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `user_tokens`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `userbadge`;
DROP TABLE IF EXISTS `badge_category`;
DROP TABLE IF EXISTS `badges`;


--
-- Table structure for table `badges`
--

CREATE TABLE IF NOT EXISTS `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badge_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `badge_category_id` int(11) NOT NULL,
  `badge_achieve_quantity` int(11) NOT NULL,
  `badge_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `badge_description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `badge_status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `badge_type_2` (`badge_type`),
  KEY `badge_category_id` (`badge_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=14 ;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `badge_type`, `badge_category_id`, `badge_achieve_quantity`, `badge_name`, `badge_description`, `badge_status`) VALUES
(1, '3_question_badge', 1, 3, 'Asked 3 Qs', 'You completed 3 questions badge!', 'active'),
(2, '5_answer_badge', 2, 5, 'Answered 5 As', 'You answered 5 answers, congrats!', 'active'),
(3, '10_answer_badge', 2, 10, 'Answered 10 As', 'You answered 10 answers, congrats!', 'active'),
(4, '10_question_badge', 1, 10, 'Completed 10 Qs', 'You completed 10 Questions, congrats!', 'active'),
(5, '5_comment_badge', 3, 5, 'Completed 5 Comments', 'You completed 5 Comments, congrats!', 'active'),
(6, '12_comment_badge', 3, 12, 'Completed 12 Comments', 'You completed 12 Comments, congrats!', 'active'),
(7, '1_post_badge', 4, 1, 'Completed 1 Post', 'You completed 1 Post, congrats!', 'active'),
(8, '10_post_badge', 4, 10, 'Completed 10 Posts', 'You completed 10 Posts, congrats!', 'active'),
(9, '20_post_badge', 4, 20, 'Completed 20 Posts', 'You completed 20 Posts, congrats!', 'active'),
(10, '3_upvote_badge', 5, 3, 'Upvoted 3 times', 'You Upvoted 3 times, congrats!', 'active'),
(11, '8_upvote_badge', 5, 8, 'Upvoted 8 times', 'You Upvoted 8 times, congrats!', 'active'),
(12, 'rep_10_reached', 6, 10, 'Reputation 10 milestone', 'Reputation 10 milestone', 'active'),
(13, 'rep_100_reached', 6, 100, 'Reputation 100 milestone', 'Reputation 100 milestone', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `badge_category`
--

CREATE TABLE IF NOT EXISTS `badge_category` (
  `badge_category_id` int(4) NOT NULL AUTO_INCREMENT,
  `badge_category` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`badge_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `badge_category`
--

INSERT INTO `badge_category` (`badge_category_id`, `badge_category`) VALUES
(1, 'question_count'),
(2, 'answer_count'),
(3, 'comment_count'),
(4, 'post_count'),
(5, 'supporter'),
(6, 'other');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_post_id` int(11) DEFAULT NULL,
  `title` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `post_moderation` enum('normal','approved','disapproved','in_review','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `marked_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `posts`
--


-- --------------------------------------------------------

--
-- Table structure for table `post_tag`
--

CREATE TABLE IF NOT EXISTS `post_tag` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `post_tag`
--


-- --------------------------------------------------------

--
-- Table structure for table `reputations`
--

CREATE TABLE IF NOT EXISTS `reputations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reputation_type` varchar(100) CHARACTER SET latin1 NOT NULL,
  `created_at` int(11) unsigned NOT NULL,
  `updated_at` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `reputations`
--


-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'login', 'Unconfirmed user, granted immediately when signin up.'),
(2, 'user', 'Confirmed user, granted after account confirmation via e-mail.'),
(3, 'admin', 'Administrative user, has access to everything.');

-- --------------------------------------------------------

--
-- Table structure for table `roles_users`
--

CREATE TABLE IF NOT EXISTS `roles_users` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `roles_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) CHARACTER SET utf8 NOT NULL,
  `value` varchar(300) CHARACTER SET utf8 NOT NULL,
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  `setting_status` enum('active','deleted','on_hold') CHARACTER SET utf8 NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=COMPACT AUTO_INCREMENT=19 ;

--
-- Dumping data for table `settings`
--

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
(15, 'own_accepted_answer', '12', 1296987527, 1296987527, 'active'),
(16, 'login_required_to_add_content', '0', 1305483176, 1305483176, 'active'),
(17, 'cache_ttl', '86400', 1305483176, 1305483176, 'active'),
(18, 'recaptcha_active', '1', 1305483176, 1305483176, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `tags`
--


-- --------------------------------------------------------

--
-- Table structure for table `userbadge`
--

CREATE TABLE IF NOT EXISTS `userbadge` (
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `userbadge`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
  `comment_count` int(6) unsigned NOT NULL DEFAULT '0',
  `account_status` enum('normal','approved','disapproved','deleted','spam','in_review') CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`),
  UNIQUE KEY `uniq_twitter_id` (`twitter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `users`
--


-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `user_tokens`
--


--
-- Constraints for dumped tables
--

--
-- Constraints for table `badges`
--
ALTER TABLE `badges`
  ADD CONSTRAINT `badges_ibfk_2` FOREIGN KEY (`badge_category_id`) REFERENCES `badge_category` (`badge_category_id`);

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

