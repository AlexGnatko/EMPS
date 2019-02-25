-- table
CREATE TEMPORARY TABLE `temp_geo_area` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `inflname` varchar(255) NOT NULL,
  `parent` bigint NOT NULL,
  `tz_id` int NOT NULL,
  `lon` float NOT NULL,
  `lat` float NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `url` (`url`),
  KEY `parent` (`parent`),
  KEY `lon` (`lon`),
  KEY `lat` (`lat`),
  KEY `cdt` (`cdt`),
  KEY `short_name` (`short_name`),
  KEY `tz_id` (`tz_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- table
CREATE TEMPORARY TABLE `temp_geo_area_location` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `location` bigint NOT NULL,
  `area` bigint NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `location` (`location`),
  KEY `area` (`area`),
  KEY `cdt` (`cdt`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- table
CREATE TEMPORARY TABLE `temp_geo_location` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lon` double NOT NULL,
  `lat` double NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `cdt` (`cdt`),
  KEY `lon` (`lon`),
  KEY `lat` (`lat`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- table
CREATE TEMPORARY TABLE `temp_geo_timezone` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `cdt` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `cdt` (`cdt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
