# discard empty attachments (attachments which have no linked attachment files)

# create temporary table containing attachment ids to delete
create temporary table iat_delete
	select iat_iss_id,iat_id from {{%issue_attachment}} iat
		left join {{%issue_attachment_file}} iaf on iaf.iaf_iat_id=iat.iat_id
	where iaf_id is null;

# delete
delete iat from {{%issue_attachment}} iat, iat_delete d
	where iat.iat_id=d.iat_id;
