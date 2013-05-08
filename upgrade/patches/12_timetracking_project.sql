ALTER TABLE %TABLE_PREFIX%time_tracking_category
	ADD COLUMN ttc_prj_id int(10) unsigned NOT NULL default 0 after ttc_id,
	DROP KEY ttc_title,
	ADD KEY ttc_title (ttc_prj_id, ttc_title);

-- select first project id - we keep this as default
select prj_id from eventum_project limit 1 into @prj_id;

-- set existing records for the first project
update %TABLE_PREFIX%time_tracking_category set ttc_prj_id=@prj_id;

-- duplicate for others
create /*temporary*/ table %TABLE_PREFIX%__migrate_ttc_prj (
	`ttc_id` int(11) NOT NULL AUTO_INCREMENT,
	`orig_ttc_id` int(10) unsigned DEFAULT NULL,
	`ttc_prj_id` int(11) unsigned NOT NULL DEFAULT '0',
	`ttc_title` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`ttc_created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`ttc_id`)
);

-- adjust auto increment position
select max(ttc_id) from %TABLE_PREFIX%time_tracking_category into @max_ttc_id;
insert into %TABLE_PREFIX%__migrate_ttc_prj set ttc_id=@max_ttc_id;

insert into %TABLE_PREFIX%__migrate_ttc_prj
select 0 ttc_id, ttc_id orig_ttc_id, prj_id ttc_prj_id, ttc_title, ttc_created_date
from %TABLE_PREFIX%time_tracking_category ttc, eventum_project prj
where prj.prj_id!=@prj_id order by ttc_prj_id;

delete from %TABLE_PREFIX%__migrate_ttc_prj where orig_ttc_id is null;

-- finally import it
insert into %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_prj_id, ttc_title, ttc_created_date)
select ttc_id, ttc_prj_id, ttc_title, ttc_created_date
from %TABLE_PREFIX%__migrate_ttc_prj;

-- TODO: migrate each project timetracking items to their proper ttc_id using orig_ttc_id;
