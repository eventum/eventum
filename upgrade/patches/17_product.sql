CREATE TABLE `%TABLE_PREFIX%product` (
  pro_id int(11) unsigned NOT NULL auto_increment,
  pro_title varchar(255) NOT NULL,
  pro_version_howto varchar(255) NOT NULL,
  pro_rank mediumint unsigned NOT NULL default 0,
  pro_removed tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY (pro_id),
  KEY (pro_rank)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE `%TABLE_PREFIX%issue_product_version` (
  ipv_id int(11) unsigned NOT NULL auto_increment,
  ipv_iss_id int(11) unsigned NOT NULL,
  ipv_pro_id int(11) unsigned NOT NULL,
  ipv_version varchar(255) NOT NULL,
  PRIMARY KEY (ipv_id),
  KEY ipv_iss_id (ipv_iss_id)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

INSERT INTO history_type (htt_name, htt_role) VALUES ('version_details_updated', '4');


CREATE TABLE `%TABLE_PREFIX%reminder_product` (
  rpr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rpr_rem_id INT(11) UNSIGNED NOT NULL,
  rpr_pro_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rpr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%custom_filter` ADD COLUMN cst_pro_id int(11) unsigned NULL AFTER cst_iss_pre_id;
