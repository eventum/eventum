ALTER TABLE %TABLE_PREFIX%time_tracking_category
	ADD COLUMN ttc_prj_id int(10) unsigned NOT NULL default 0 after ttc_id,
	DROP KEY ttc_title,
	ADD KEY ttc_title (ttc_prj_id, ttc_title);

-- select first project id - we keep this as default
select prj_id from eventum_project limit 1 into @prj_id;

-- set existing records for the first project
update %TABLE_PREFIX%time_tracking_category set ttc_prj_id=@prj_id;

-- duplicate for others
create temporary table migrate_ttc_prj (
	`ttc_id` int(11) NOT NULL AUTO_INCREMENT,
	`orig_ttc_id` int(10) unsigned DEFAULT NULL,
	`ttc_prj_id` int(11) unsigned NOT NULL DEFAULT '0',
	`ttc_title` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`ttc_created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`ttc_id`)
);

-- adjust auto increment position
select max(ttc_id) from %TABLE_PREFIX%time_tracking_category into @max_ttc_id;
insert into migrate_ttc_prj set ttc_id=@max_ttc_id;

insert into migrate_ttc_prj
select 0 ttc_id, ttc_id orig_ttc_id, prj_id ttc_prj_id, ttc_title, ttc_created_date
from %TABLE_PREFIX%time_tracking_category ttc, eventum_project prj
where prj.prj_id!=@prj_id order by ttc_prj_id;

delete from migrate_ttc_prj where orig_ttc_id is null;

-- finally import it
insert into %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_prj_id, ttc_title, ttc_created_date)
select ttc_id, ttc_prj_id, ttc_title, ttc_created_date
from migrate_ttc_prj;

-- migrate each project timetracking items to their proper ttc_id using orig_ttc_id;
create temporary table migrate_issues_by_project
select iss_id,iss_prj_id from %TABLE_PREFIX%issue where iss_prj_id!=@prj_id;

create temporary table migrate_ttr_mapping
select distinct ttr_ttc_id old_ttr_ttc_id,ttc_id new_ttr_ttc_id, iss_id
from %TABLE_PREFIX%time_tracking ttr, migrate_issues_by_project iss, migrate_ttc_prj ttc
where ttr_iss_id=iss_id and orig_ttc_id=ttr_ttc_id and ttc_prj_id=iss_prj_id;

alter table migrate_ttr_mapping add key (old_ttr_ttc_id);

update %TABLE_PREFIX%time_tracking ttr, migrate_ttr_mapping m
set ttr_ttc_id=new_ttr_ttc_id where ttr_ttc_id=old_ttr_ttc_id and iss_id=ttr_iss_id;
