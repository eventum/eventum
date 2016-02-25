/**
 * Adds a user preference for collapsed_emails.
 *
 * @see https://github.com/eventum/eventum/pull/143
 */
ALTER TABLE {{%user_preference}} ADD COLUMN upr_collapsed_emails TINYINT (1) NULL DEFAULT 1;
