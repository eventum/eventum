CREATE TABLE `%TABLE_PREFIX%project_severity` (
  sev_id smallint(3) unsigned NOT NULL auto_increment,
  sev_prj_id int(11) unsigned NOT NULL,
  sev_title varchar(64) NOT NULL default '',
  sev_description varchar(255) NULL,
  sev_rank TINYINT(1) NOT NULL,
  PRIMARY KEY (sev_id),
  UNIQUE KEY sev_title (sev_title, sev_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%project_severity` VALUES (1,1,'S1 ','Total Production Outage',0);
INSERT INTO `%TABLE_PREFIX%project_severity` VALUES (2,1,'S2','Serious Production Failure',1);
INSERT INTO `%TABLE_PREFIX%project_severity` VALUES (3,1,'S3','Minor Failure',2);
INSERT INTO `%TABLE_PREFIX%project_severity` VALUES (4,1,'S4','General Requests',3);

ALTER TABLE `%TABLE_PREFIX%issue` ADD COLUMN iss_sev_id int(11) unsigned NOT NULL default 0 AFTER iss_pri_id;
ALTER TABLE `%TABLE_PREFIX%custom_filter` ADD COLUMN cst_iss_sev_id int(10) unsigned NULL AFTER cst_iss_pri_id;

CREATE TABLE `%TABLE_PREFIX%reminder_severity` (
  rms_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rms_rem_id INT(11) UNSIGNED NOT NULL,
  rms_sev_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rms_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

