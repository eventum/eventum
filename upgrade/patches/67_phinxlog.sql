# init phinx table so that it thinks it's already initialized

CREATE TABLE `phinxlog` (
	`version` bigint(20) NOT NULL,
	`migration_name` varchar(100) DEFAULT NULL,
	`start_time` timestamp NULL DEFAULT NULL,
	`end_time` timestamp NULL DEFAULT NULL,
	`breakpoint` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `phinxlog` VALUES (20170427170945,'InitDatabase','2017-04-28 17:07:14','2017-04-28 17:07:15',0);
