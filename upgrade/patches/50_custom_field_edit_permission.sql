/**
 * Adds the ability to allow a custom field to be viewed but not edited
 *
 * @see https://github.com/eventum/eventum/pull/149
 */
ALTER TABLE {{%custom_field}} ADD fld_min_role_edit tinyint(1) NOT NULL DEFAULT 0 AFTER fld_min_role;
UPDATE {{%custom_field}} SET fld_min_role_edit = fld_min_role;
