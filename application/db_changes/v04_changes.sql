INSERT INTO roles_users VALUES (1, 3);

ALTER TABLE `posts` CHANGE `post_status` `post_status` ENUM('published','accepted','closed','marked_anonymous') CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL DEFAULT 'published', CHANGE `post_moderation` `post_moderation` ENUM('normal','disapproved','in_review','deleted') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal'

ALTER TABLE `posts` CHANGE `post_moderation` `post_moderation` ENUM('normal','approved','disapproved','in_review','deleted') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal'

ALTER TABLE  `posts` DROP  `post_status`

ALTER TABLE `posts`  ADD `accepted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `post_moderation`,  
ADD `marked_anonymous` TINYINT(1) NOT NULL DEFAULT '0' AFTER `accepted`,  
ADD `closed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `marked_anonymous`

ALTER TABLE `users` CHANGE `account_status` `account_status` ENUM('normal','approved','disapproved','deleted','spam','in_review') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES
(16, 'login_required_to_add_content', '0', 1305483176, 1305483176, 'active');
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`, `setting_status`) VALUES
(17, 'cache_ttl', '86400', 1305483176, 1305483176, 'active');
