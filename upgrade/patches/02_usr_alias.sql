ALTER TABLE %TABLE_PREFIX%issue CHANGE COLUMN iss_res_id iss_res_id int(10) unsigned NULL DEFAULT NULL;
CREATE TABLE %TABLE_PREFIX%user_alias (
	ual_usr_id int(11) unsigned not null,
	ual_email varchar(255),
	PRIMARY KEY(ual_usr_id, ual_email),
	UNIQUE(ual_email)
);
