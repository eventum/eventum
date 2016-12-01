/**
 * Add icon of priority for project.
 *
 * @see https://github.com/eventum/eventum/pull/224
 */
ALTER TABLE {{%project_priority}} ADD COLUMN pri_icon TINYINT (2) NOT NULL default 0;
