-- change version table to be log based
alter table {{%version}}
    add `ver_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- fill old entries being applied
-- note "36" is not in the list, as that will be present via alter statement
set @ts=now();
insert into {{%version}} (ver_version,ver_timestamp) values
     (1,@ts),  (2,@ts),  (3,@ts),  (4,@ts),  (5,@ts),  (6,@ts),  (7,@ts),  (8,@ts),  (9,@ts), (10,@ts),
    (11,@ts), (12,@ts), (13,@ts), (14,@ts), (15,@ts), (16,@ts), (17,@ts), (18,@ts), (19,@ts), (20,@ts),
    (21,@ts), (22,@ts), (23,@ts), (24,@ts), (25,@ts), (26,@ts), (27,@ts), (28,@ts), (29,@ts), (30,@ts),
    (31,@ts), (32,@ts), (33,@ts), (34,@ts), (35,@ts);
