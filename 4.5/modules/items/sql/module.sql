-- table
CREATE TEMPORARY TABLE `temp_ws_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `link` varchar(32) NOT NULL,
  `extra` varchar(255) NOT NULL,
  `ord` int(11) NOT NULL,
  `amount` decimal(11,3) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `descr` text NOT NULL,
  `base_id` int(11) NOT NULL,
  `pub` tinyint(4) NOT NULL,
  `cdt` int(11) NOT NULL,
  `dt` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `pub` (`pub`),
  KEY `base_id` (`base_id`),
  KEY `price` (`price`),
  KEY `link` (`link`),
  KEY `ord` (`ord`),
  KEY `amount` (`amount`),
  FULLTEXT KEY `descr` (`descr`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- table
CREATE TEMPORARY TABLE `temp_ws_items_structure` (
  `item_id` int(11) NOT NULL,
  `structure_id` int(11) NOT NULL,
  KEY `item_id` (`item_id`),
  KEY `structure_id` (`structure_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- table
CREATE TEMPORARY TABLE `temp_ws_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `full_id` varbinary(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `descr` text NOT NULL,
  `qty` int(11) NOT NULL,
  `pub` tinyint(4) NOT NULL,
  `ord` int(11) NOT NULL,
  `cdt` int(11) NOT NULL,
  `dt` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `cdt` (`cdt`),
  KEY `dt` (`dt`),
  KEY `url` (`url`),
  KEY `ord` (`ord`),
  KEY `type` (`type`),
  KEY `full_id` (`full_id`),
  KEY `name` (`name`),
  KEY `qty` (`qty`),
  KEY `pub` (`pub`),
  FULLTEXT KEY `descr` (`descr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

