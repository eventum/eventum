ALTER TABLE %TABLE_PREFIX%issue_checkin
	MODIFY COLUMN isc_old_version varchar(40) default null,
	MODIFY COLUMN isc_new_version varchar(40) default null;
