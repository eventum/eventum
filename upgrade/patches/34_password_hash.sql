ALTER TABLE {{%user}}
  MODIFY COLUMN usr_password varchar(60) NOT NULL DEFAULT '';

# reset account passwords who have empty passwords
update {{%user}} set usr_password='' where usr_password=md5('');
