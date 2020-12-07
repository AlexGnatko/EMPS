--
-- Write regular MySQL queries to create the tables for this database.
-- Add TEMPORARY everywhere.
-- Replace the project's "TP" constant with "temp_".
-- For example, if the TP constant is "c_", and you want the final table to be "c_e_content", use "temp_e_content"

-- HOW IT WORKS:
-- A special script will create the temporary tables and check their structure against the existing corresponding tables.
-- If a corresponding table is not found in the database, the SQL command will be changed to replace "TEMPORARY"
-- and change "temp_" to whatever is the value of TP ("c_").

-- If the data structure in this file doesn't have some fields that exist in the real tables, those fields in the real
-- tables will be left as they are.

-- WARNING! If you are RENAMING a column instead of adding, and want the new column to keep the data from the old column,
-- then have the field manually renamed in the database. Otherwise the script will create another column with no data.

-- Please put "-- table" before each table creating statement. That's to make it easier to divide the code into
-- individual statements.

-- Table name is the piece of text inside the first occurrence of a pair of ` accents in a table statement.
-- So, the accents are required, otherwise EMPS will not be able to detect the table name.

-- table
CREATE TEMPORARY TABLE `temp_e_actkeys` (
  `pin` char(32) NOT NULL DEFAULT '',
  `user_id` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  KEY `pin` (`pin`),
  KEY `uid` (`user_id`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_content` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` int(18) NOT NULL DEFAULT '0',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `lang` char(2) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT 'p',
  `cdt` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `context_id` (`context_id`),
  KEY `uri` (`uri`),
  KEY `lang` (`lang`),
  KEY `type` (`type`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_contexts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `ref_type` int(11) NOT NULL DEFAULT '0',
  `ref_sub` int(11) NOT NULL DEFAULT '0',
  `ref_id` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ref_type` (`ref_type`),
  KEY `ref_sub` (`ref_sub`),
  KEY `ref_id` (`ref_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_ctx_links` (
  `ctx_1_id` int(18) NOT NULL DEFAULT '0',
  `ctx_2_id` int(18) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  KEY `ctx_1_id` (`ctx_1_id`),
  KEY `ctx_2_id` (`ctx_2_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_files` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) NOT NULL,
  `context_id` int(18) NOT NULL DEFAULT '0',
  `content_type` varchar(255) NOT NULL DEFAULT '',
  `file_name` varchar(255) NOT NULL DEFAULT '',
  `descr` text NOT NULL DEFAULT '',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `folder` varchar(16) NOT NULL DEFAULT '',
  `size` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  `offloaded` int(11) NOT NULL DEFAULT '0',
  `ord` int(11) NOT NULL DEFAULT '0',
  `user_id` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `content_type` (`content_type`),
  KEY `file_name` (`file_name`),
  KEY `size` (`size`),
  KEY `dt` (`dt`),
  KEY `md5` (`md5`),
  KEY `context_id` (`context_id`),
  KEY `signature` (`user_id`),
  KEY `offloaded` (`offloaded`),
  KEY `ord` (`ord`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_aws_offloading` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '0',
  `object_id` bigint NOT NULL DEFAULT '0',
  `direct_url` varchar(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `size` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `object_id` (`object_id`),
  KEY `size` (`size`),
  KEY `dt` (`dt`),
  KEY `direct_url` (`direct_url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_menu` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` bigint NOT NULL,
  `uri` varchar(255) NOT NULL,
  `lang` char(2) NOT NULL DEFAULT '',
  `parent` bigint NOT NULL,
  `grp` varchar(32) NOT NULL,
  `ord` int(11) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `lang` (`lang`),
  KEY `parent` (`parent`),
  KEY `grp` (`grp`),
  KEY `ord` (`ord`),
  KEY `enabled` (`enabled`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` bigint NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL,
  `phone` varchar(64) NOT NULL,
  `msg` text NOT NULL,
  `dt` bigint NOT NULL,
  `sent` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dt` (`dt`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_msgcache` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL DEFAULT '0',
  `to` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `title` text COLLATE utf8_bin NOT NULL,
  `message` mediumtext COLLATE utf8_bin NOT NULL,
  `params` blob NOT NULL,
  `smtpdata` blob NOT NULL,
  `dt` bigint NOT NULL DEFAULT '0',
  `sdt` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `dt` (`dt`),
  KEY `sdt` (`sdt`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_bin;

-- table
CREATE TEMPORARY TABLE `temp_e_smscache` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL DEFAULT '0',
  `to` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `message` text COLLATE utf8_bin NOT NULL,
  `params` blob NOT NULL,
  `dt` bigint NOT NULL DEFAULT '0',
  `sdt` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `dt` (`dt`),
  KEY `sdt` (`sdt`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_bin;

-- table
CREATE TEMPORARY TABLE `temp_e_pincode` (
  `pincode` int(11) NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  `access` int(11) NOT NULL DEFAULT '0',
  KEY `pincode` (`pincode`),
  KEY `dt` (`dt`),
  KEY `access` (`access`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_posts_topics` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` bigint NOT NULL DEFAULT '0',
  `topic_id` bigint NOT NULL DEFAULT '0',
  `cdt` bigint NOT NULL DEFAULT '0',
  `dt` bigint NOT NULL DEFAULT '0',
  `ord` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `ord` (`ord`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_properties` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `idx` int(11) NOT NULL,
  `type` char(1) NOT NULL,
  `code` varchar(32) NOT NULL,
  `context_id` bigint NOT NULL,
  `v_int` bigint DEFAULT NULL,
  `v_char` varchar(255) DEFAULT NULL,
  `v_text` mediumtext,
  `v_data` mediumtext,
  `v_float` float DEFAULT NULL,
  `dt` bigint NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ref_id` (`context_id`),
  KEY `code` (`code`),
  KEY `type` (`type`),
  KEY `user_id` (`idx`),
  KEY `dt` (`dt`),
  KEY `status` (`status`),
  KEY `v_int` (`v_int`),
  KEY `v_char` (`v_char`),
  KEY `v_float` (`v_float`),
  FULLTEXT KEY `v_search` (`v_char`,`v_text`,`v_data`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_property_references` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` bigint NOT NULL,
  `property_id` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `context_id` (`context_id`),
  KEY `property_id` (`property_id`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_redirect` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `olduri` varchar(255) NOT NULL,
  `newuri` varchar(255) NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `olduri` (`olduri`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_browsers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_blacklist` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `adt` bigint NOT NULL,
  `edt` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `adt` (`adt`),
  KEY `edt` (`edt`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_watchlist` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `cnt` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cnt` (`cnt`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `ip` varchar(255) NOT NULL,
  `browser_id` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_php_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `sess_id` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `browser_id` bigint NOT NULL,
  `data` mediumtext NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `sess_id` (`sess_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_shadows` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `website_ctx` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `website_ctx` (`website_ctx`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_thumbs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `upload_id` bigint NOT NULL DEFAULT '0',
  `size` varchar(128) NOT NULL,
  `dt` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `upload_id` (`upload_id`),
  KEY `dt` (`dt`),
  KEY `size` (`size`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_topics` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `dt` bigint NOT NULL DEFAULT '0',
  `user_id` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dt` (`dt`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_uploads` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) NOT NULL,
  `size` bigint NOT NULL DEFAULT '0',
  `psize` varchar(64) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `wmark` tinyint(4) NOT NULL,
  `qual` tinyint(4) NOT NULL,
  `protect` tinyint(4) NOT NULL,
  `type` varchar(255) NOT NULL,
  `new_type` varchar(255) NOT NULL,
  `thumb` varchar(128) NOT NULL DEFAULT '0',
  `folder` varchar(255) NOT NULL,
  `descr` text NOT NULL,
  `context_id` int(18) NOT NULL,
  `upper_context_id` int(18) NOT NULL,
  `ord` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `md5` (`md5`),
  KEY `size` (`size`),
  KEY `context_id` (`context_id`),
  KEY `ord` (`ord`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_userlog` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `code` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `site` tinyint(4) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `context_id` bigint NOT NULL,
  `username` varchar(255) NOT NULL,
  `profile_name` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  `fullname` text NOT NULL,
  `status` int(11) NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `profile_name` (`profile_name`),
  KEY `status` (`status`),
  KEY `cdt` (`cdt`),
  KEY `type` (`type`),
  KEY `site` (`site`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_users_groups` (
  `user_id` bigint NOT NULL DEFAULT '0',
  `group_id` varchar(16) NOT NULL,
  `context_id` bigint NOT NULL,
  KEY `user_id` (`user_id`,`group_id`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_videos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `context_id` bigint NOT NULL,
  `cdt` int(10) unsigned NOT NULL,
  `rating` int(11) NOT NULL,
  `votes` int(10) unsigned NOT NULL,
  `views` int(10) unsigned NOT NULL,
  `rutube_id` char(64) DEFAULT NULL,
  `youtube_id` varchar(64) DEFAULT NULL,
  `vimeo_id` varchar(64) DEFAULT NULL,
  `screencast_url` text DEFAULT NULL,  
  `duration` int(10) unsigned NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `status` int(10) unsigned NOT NULL,
  `ord` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rating` (`rating`),
  KEY `user_id` (`user_id`),
  KEY `context_id` (`context_id`),
  KEY `cdt` (`cdt`),
  KEY `youtube_id` (`youtube_id`),
  KEY `ord` (`ord`),
  FULLTEXT KEY `name` (`name`,`description`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_bin;

-- table
CREATE TEMPORARY TABLE `temp_e_websites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `hostname_filter` varchar(255) NOT NULL,
  `lang` varchar(8) NOT NULL,
  `status` int(11) NOT NULL,
  `pub` tinyint(4) NOT NULL,
  `user_id` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `hostname` (`hostname`),
  KEY `hostname_filter` (`hostname_filter`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `parent` (`parent`),
  KEY `pub` (`pub`),
  KEY `lang` (`lang`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `url` (`url`),
  KEY `cdt` (`cdt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_identities` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `identity` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `user_id` bigint NOT NULL,
  `photo` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `provider` (`provider`),
  KEY `identity` (`identity`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_cache` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `context_id` bigint NOT NULL,  
  `code` varchar(255) NOT NULL,  
  `data` longtext NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `context_id` (`context_id`),
  KEY `code` (`code`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci ;

-- table
CREATE TEMPORARY TABLE `temp_e_counter` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(8) NOT NULL,
  `context_id` bigint NOT NULL,
  `per` int(11) NOT NULL,
  `vle` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `per` (`per`),
  KEY `vle` (`vle`),
  KEY `dt` (`dt`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_unique_texts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type_code` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `context_id` bigint NOT NULL,
  `website_ctx` bigint NOT NULL,
  `unique_text` mediumtext NOT NULL,
  `upload_log` mediumtext NOT NULL,
  `status_yandex` int(11) NOT NULL,
  `cdt` bigint NOT NULL,
  `dt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_code` (`type_code`),
  KEY `status_yandex` (`status_yandex`),
  KEY `website_ctx` (`website_ctx`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_track_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `descr` text NOT NULL,
  `cdt` int(11) NOT NULL,
  `dt` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `phone` (`phone`),
  KEY `type` (`type`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `ip` (`ip`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- table
CREATE TEMPORARY TABLE `temp_e_data_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `cdt` int(11) NOT NULL,
  `dt` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `value` (`value`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;
