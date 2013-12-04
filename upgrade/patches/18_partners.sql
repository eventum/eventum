ALTER TABLE `%TABLE_PREFIX%user` ADD COLUMN usr_par_code varchar(30) NULL;

CREATE TABLE `%TABLE_PREFIX%partner_project` (
  pap_prj_id int(11) unsigned NOT NULL,
  pap_par_code varchar(30) NOT NULL,
  PRIMARY KEY(pap_prj_id, pap_par_code)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE `%TABLE_PREFIX%issue_partner` (
  ipa_iss_id int(11) unsigned NOT NULL,
  ipa_par_code varchar(30) NOT NULL,
  ipa_created_date DATETIME NOT NULL,
  PRIMARY KEY(ipa_iss_id, ipa_par_code)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'partner_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'partner_removed', htt_role = 4;