# unify field type for pri_id
# https://bugs.launchpad.net/eventum/+bug/1450152

ALTER TABLE {{%issue}}        modify iss_pri_id smallint(3) unsigned NOT NULL default 0;
ALTER TABLE {{%project_priority}} modify pri_id smallint(3) unsigned NOT NULL auto_increment;
