CREATE TABLE {{%api_token}} (
  apt_id int(11) unsigned NOT NULL auto_increment,
  apt_usr_id int(11) unsigned NOT NULL,
  apt_created datetime NOT NULL,
  apt_expires datetime NOT NULL,
  apt_status varchar(10) NOT NULL default 'active',
  apt_token varchar(255) NOT NULL,
  PRIMARY KEY (apt_id),
  KEY (apt_token)
) ENGINE=MYISAM DEFAULT CHARSET=utf8;
