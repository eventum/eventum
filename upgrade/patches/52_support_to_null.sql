/**
 * Allows the to column of the support table to be null
 */
ALTER TABLE {{%support_email}} CHANGE COLUMN sup_to sup_to TEXT NULL DEFAULT NULL;