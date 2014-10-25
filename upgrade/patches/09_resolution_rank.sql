ALTER TABLE {{%resolution}} ADD COLUMN res_rank int(2) NOT NULL;
UPDATE {{%resolution}} SET res_rank=res_id;
