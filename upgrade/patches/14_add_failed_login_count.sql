ALTER TABLE %TABLE_PREFIX%user
   ADD COLUMN usr_last_login DATETIME DEFAULT NULL,
   ADD COLUMN usr_last_failed_login DATETIME DEFAULT NULL,
   ADD COLUMN usr_failed_logins int(11) unsigned NOT NULL DEFAULT 0;
