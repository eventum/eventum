alter table {{%issue_checkin}}
	add isc_commitid varchar(40) binary after isc_iss_id;
