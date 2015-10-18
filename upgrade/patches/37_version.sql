# change version table to be log based

alter table {{%version}}
    add `ver_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    add `ver_comment` varchar(255) DEFAULT NULL;
