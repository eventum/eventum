/**
 * Control the order of custom fields
 *
 */
ALTER TABLE {{%custom_field_option}} ADD COLUMN cfo_rank int(10) NOT NULL DEFAULT 0;
ALTER TABLE {{%custom_field}} ADD COLUMN fld_order_by varchar(20) NOT NULL DEFAULT 'cfo_id ASC'