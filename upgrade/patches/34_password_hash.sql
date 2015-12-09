ALTER TABLE {{%user}}
  DROP KEY `usr_email_password`,
  MODIFY COLUMN usr_password varchar(255) NOT NULL DEFAULT '';

# reset account passwords who have empty passwords
update {{%user}} set usr_password='' where usr_password=md5('');
