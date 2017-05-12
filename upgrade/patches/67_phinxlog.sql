# init phinx table so that it thinks it's already initialized

CREATE TABLE `phinxlog` (
	`version` bigint(20) NOT NULL,
	`migration_name` varchar(100) DEFAULT NULL,
	`start_time` timestamp NULL DEFAULT NULL,
	`end_time` timestamp NULL DEFAULT NULL,
	`breakpoint` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

SET @now := current_timestamp(), @bp := 0;

INSERT INTO `phinxlog` SET version=20170427170945, migration_name='EventumInitDatabase', start_time=@now, end_time=@now, breakpoint=@bp;
INSERT INTO `phinxlog` SET version=20170428180919, migration_name='EventumInitialData',  start_time=@now, end_time=@now, breakpoint=@bp;
