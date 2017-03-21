/**
 * Add per project recipient type flags.
 */
ALTER TABLE {{%project}} ADD COLUMN prj_recipient_type_flag VARCHAR(255) NULL DEFAULT NULL AFTER prj_outgoing_sender_email;
ALTER TABLE {{%project}} ADD COLUMN prj_flag_location VARCHAR(6) NULL DEFAULT NULL AFTER prj_recipient_type_flag;
