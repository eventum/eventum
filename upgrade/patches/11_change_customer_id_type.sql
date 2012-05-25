ALTER TABLE %TABLE_PREFIX%issue CHANGE COLUMN iss_customer_id iss_customer_id varchar(128) null;
ALTER TABLE %TABLE_PREFIX%support_email CHANGE COLUMN sup_customer_id sup_customer_id varchar(128) null;
ALTER TABLE %TABLE_PREFIX%user CHANGE COLUMN usr_customer_id usr_customer_id varchar(128) null;
ALTER TABLE %TABLE_PREFIX%customer_note CHANGE COLUMN cno_customer_id cno_customer_id varchar(128) not null;
ALTER TABLE %TABLE_PREFIX%customer_account_manager CHANGE COLUMN cam_customer_id cam_customer_id varchar(128) not null;
ALTER TABLE %TABLE_PREFIX%reminder_requirement CHANGE COLUMN rer_customer_id rer_customer_id varchar(128) null;
ALTER TABLE %TABLE_PREFIX%issue CHANGE COLUMN iss_customer_contact_id iss_customer_contact_id varchar(128) null;
ALTER TABLE %TABLE_PREFIX%user CHANGE COLUMN usr_customer_contact_id usr_customer_contact_id varchar(128) null;