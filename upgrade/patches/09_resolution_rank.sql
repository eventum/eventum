ALTER TABLE %TABLE_PREFIX%resolution ADD COLUMN res_rank int(2) NOT NULL;
UPDATE %TABLE_PREFIX%resolution SET res_rank=res_id;
