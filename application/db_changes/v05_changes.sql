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

--
-- Constraints for dumped tables
--

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

--
-- Constraints for table `badges`
--
ALTER TABLE `badges`
  ADD CONSTRAINT `badges_ibfk_2` FOREIGN KEY (`badge_category_id`) REFERENCES `badge_category` (`badge_category_id`);
  
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


INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES
(18, 'recaptcha_active', '1', 1305483176, 1305483176, 'active');

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES 
(19, 'badge_activated', '1', '1306647715', '1306647715', 'active');

ALTER TABLE `users` ADD `comment_count` INT( 6 ) UNSIGNED NOT NULL AFTER `answer_count` 

