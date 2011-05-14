INSERT INTO roles_users VALUES (1, 3);

ALTER TABLE `posts` CHANGE `post_status` `post_status` ENUM('published','accepted','closed','marked_anonymous') CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL DEFAULT 'published', CHANGE `post_moderation` `post_moderation` ENUM('normal','disapproved','in_review','deleted') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal'

ALTER TABLE `posts` CHANGE `post_moderation` `post_moderation` ENUM('normal','approved','disapproved','in_review','deleted') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal'

ALTER TABLE  `posts` DROP  `post_status`

ALTER TABLE `posts`  ADD `accepted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `post_moderation`,  
ADD `marked_anonymous` TINYINT(1) NOT NULL DEFAULT '0' AFTER `accepted`,  
ADD `closed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `marked_anonymous`
