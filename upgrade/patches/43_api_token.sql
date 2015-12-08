CREATE TABLE {{%api_token}} (
  apt_id int unsigned NOT NULL auto_increment,
  apt_usr_id int unsigned NOT NULL,
  apt_created datetime NOT NULL,
  apt_status varchar(10) NOT NULL default 'active',
  apt_token varchar(32) NOT NULL,
  PRIMARY KEY (apt_id),
  KEY(apt_usr_id, apt_status),
  KEY (apt_token)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;
