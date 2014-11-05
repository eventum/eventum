ALTER TABLE {{%issue_checkin}}
  ADD isc_reponame VARCHAR(255) DEFAULT '' NOT NULL AFTER isc_iss_id;

UPDATE {{%issue_checkin}}
  SET isc_reponame='default';

# alter these to be just NULL
update {{%issue_checkin}} set isc_old_version=NULL where isc_old_version='NONE';
update {{%issue_checkin}} set isc_new_version=NULL where isc_new_version='NONE';
