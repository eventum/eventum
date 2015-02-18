# add date column to eventum_issue_attachment_file table
# this can be used to cleanup stale uploads with ajax based uploads
alter table eventum_issue_attachment_file
    add iaf_created_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL
    after iaf_filesize;

# fill initial timestamp based on eventum_issue_attachment timestamp
update eventum_issue_attachment_file iaf
    join eventum_issue_attachment iat on iaf.iaf_iat_id=iat.iat_id
    set iaf_created_date=iat_created_date
where iaf_created_date='0000-00-00 00:00:00';
