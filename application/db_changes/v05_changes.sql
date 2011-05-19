--
-- Table structure for table `badges`
--

CREATE TABLE IF NOT EXISTS `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badge_type_id` int(4) NOT NULL,
  `badge_achieve_quantity` int(11) NOT NULL,
  `badge_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `badge_description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `badge_status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `badge_type` (`badge_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `badge_type`
--

CREATE TABLE IF NOT EXISTS `badge_type` (
  `badge_type_id` int(4) NOT NULL AUTO_INCREMENT,
  `badge_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`badge_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------


--
-- Constraints for table `badges`
--
ALTER TABLE `badges`
  ADD CONSTRAINT `badges_ibfk_1` FOREIGN KEY (`badge_type_id`) REFERENCES `badge_type` (`badge_type_id`);
  
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES
(18, 'config.cache_ttl_badges', '86400', 1305483176, 1305483176, 'active');

ALTER TABLE `users`  ADD `comment_count` INT(6) UNSIGNED NOT NULL DEFAULT '0' AFTER `answer_count`;

--
-- Table structure for table `user_badges`
--

CREATE TABLE IF NOT EXISTS `user_badges` (
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `badge_type` (`badge_type_id`, `badge_type`) VALUES
(1, 'question_count'),
(2, 'answer_count'),
(3, 'comment_count'),
(4, 'post_count'),
(5, 'supporter'),
(6, 'awesome_writer'),
(7, 'other');


