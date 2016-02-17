ALTER TABLE {{%issue}} ADD COLUMN iss_access_level varchar(150) NOT NULL DEFAULT "normal";

INSERT INTO {{%history_type}} (htt_name, htt_role) VALUES ('access_level_changed', '4');
INSERT INTO {{%history_type}} (htt_name, htt_role) VALUES ('access_list_added', '4');
INSERT INTO {{%history_type}} (htt_name, htt_role) VALUES ('access_list_removed', '4');

CREATE TABLE {{%issue_access_log}} (
  alg_id int unsigned NOT NULL auto_increment,
  alg_iss_id int unsigned NOT NULL,
  alg_usr_id int unsigned NOT NULL,
  alg_failed tinyint(1) NOT NULL DEFAULT 0,
  alg_item_id int unsigned NULL,
  alg_created datetime NOT NULL,
  alg_ip_address VARCHAR(15) NOT NULL,
  alg_item varchar(10) NULL,
  alg_url varchar(255) NULL,
  PRIMARY KEY (alg_id),
  KEY(alg_iss_id)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE {{%issue_access_list}} (
  ial_id int unsigned NOT NULL auto_increment,
  ial_iss_id int unsigned NOT NULL,
  ial_usr_id int unsigned NOT NULL,
  ial_created datetime NOT NULL,
  PRIMARY KEY (ial_id),
  KEY(ial_iss_id, ial_usr_id)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

UPDATE {{%issue}} SET iss_access_level = 'assignees_only' WHERE iss_private = 1;

ALTER TABLE {{%issue}} DROP COLUMN iss_private;

DELETE FROM {{%project_field_display}} WHERE pfd_field = 'private'
