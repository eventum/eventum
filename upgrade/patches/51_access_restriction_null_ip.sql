/**
 * Allows the IP column for access log to be null
 */
ALTER TABLE {{%issue_access_log}} CHANGE COLUMN alg_ip_address alg_ip_address varchar(15) NULL DEFAULT NULL;