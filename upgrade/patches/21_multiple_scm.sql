ALTER TABLE {{%issue_checkin}}
  ADD isc_reponame VARCHAR(255) DEFAULT '' NOT NULL AFTER isc_iss_id;

UPDATE {{%issue_checkin}}
  SET isc_reponame='default';
