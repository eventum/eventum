# these patches lacked Engine and Charset definition
# - upgrade/patches/02_usr_alias.sql
# - upgrade/patches/45_multiple_groups.php
# - upgrade/patches/07_user_preference.php
# altho they likely do not have non-utf8- data on text columns,
# update them for consistency.

ALTER TABLE {{%user_alias}}
    ENGINE = MyISAM DEFAULT CHARSET = utf8;

ALTER TABLE {{%user_group}}
    ENGINE = MyISAM DEFAULT CHARSET = utf8;

ALTER TABLE {{%user_preference}}
    ENGINE = MyISAM DEFAULT CHARSET = utf8;

ALTER TABLE {{%user_project_preference}}
    ENGINE = MyISAM DEFAULT CHARSET = utf8;
