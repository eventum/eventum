ALTER TABLE {{%note}} CHANGE COLUMN not_blocked_message not_full_message longblob NULL;
ALTER TABLE {{%support_email_body}} CHANGE seb_full_email seb_full_email longblob NOT NULL;
ALTER TABLE {{%mail_queue}} CHANGE COLUMN maq_body maq_body longblob NOT NULL;
ALTER TABLE {{%note}} ADD COLUMN not_is_blocked tinyint(1) NOT NULL DEFAULT 0;
UPDATE {{%note}} SET not_is_blocked = 1 WHERE not_full_message != '';
