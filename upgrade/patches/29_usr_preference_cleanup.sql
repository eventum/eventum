-- cleanup now unused column migrated in 07 patch
alter table {{%user}}
    drop column usr_preferences;
