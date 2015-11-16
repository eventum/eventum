-- Add field to allow custom fields to be marked as required when editing
alter table {{%custom_field}}
    add `fld_edit_form_required` tinyint(1) NOT NULL DEFAULT 0;