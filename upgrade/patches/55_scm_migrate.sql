/*
 * migrate to new scm tables
 * - eventum_commit - commit general info (author, date, message)
 * - eventum_commit_file - file details in commit
 * - eventum_issue_commit - relation of commit to issue
 */

# create commits
INSERT INTO {{%commit}}
  (com_id, com_scm_name, com_changeset, com_author_name, com_commit_date, com_message)
  SELECT
    max(isc_id),
    isc_reponame,
    isc_commitid,
    isc_username,
    MIN(isc_created_date),
    isc_commit_msg
  FROM {{%issue_checkin}}
  GROUP BY isc_commitid order by isc_created_date asc;

# move author_name to author_email column if it contains '@' character
UPDATE {{%commit}}
  SET com_author_email = com_author_name, com_author_name = NULL
  WHERE com_author_name LIKE '%@%';

# FIXME, should this be unique per reponame/branch
alter table {{%commit}} add index (com_changeset);
alter table {{%issue_checkin}} add index (isc_commitid);

# create file details
INSERT INTO {{%commit_file}}
  (cof_com_id, cof_filename, cof_added, cof_removed, cof_modified, cof_old_version, cof_new_version)
  SELECT
    com.com_id,
    concat(isc_module, '/', isc_filename),
    isnull(isc_old_version) added,
    isnull(isc_new_version) removed,
    not isnull(isc_old_version) and not isnull(isc_new_version) modified,
    isc_old_version,
    isc_new_version
  FROM {{%issue_checkin}} isc, {{%commit}} com
  WHERE isc.isc_commitid = com.com_changeset;

# reset bogus data, such as "imported sources" from CVS
update {{%commit_file}} set cof_added=0,cof_removed=0 where cof_added+cof_modified+cof_removed>1;

# create issue relations
INSERT INTO {{%issue_commit}}
  (isc_iss_id, isc_com_id)
  SELECT
    isc_iss_id,
    com.com_id
  FROM {{%issue_checkin}} isc, {{%commit}} com
  WHERE isc.isc_commitid = com.com_changeset
  GROUP BY com.com_changeset;

alter table {{%commit}} drop index com_changeset;
