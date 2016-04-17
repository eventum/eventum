
# commit info
create table {{%commit}} (
  com_id int unsigned not null auto_increment primary key,
  # commit repo id

  # scm name (scm definition in setup.php), previously isc_reponame field
  com_scm_name varchar(255) NOT NULL DEFAULT 'default',

  # project, i.e 'eventum/eventum' in gitlab/github
  # directory in CVS, SVN(trac)
  # TODO, add patch to convert this to filename field for CVS/SVN
  com_project_name varchar(255) NULL,

  # scm changeset, 40 chars to fit git commit hashes
  com_changeset varchar(40) not null,
  com_branch varchar(255) NULL,
  com_author_email varchar(255) DEFAULT NULL,
  com_author_name varchar(255) DEFAULT NULL,
  com_usr_id int default null,
  com_commit_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  com_message mediumtext
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

# commit file details (for CVS and SVN)
create table {{%commit_file}} (
  # commit file id
  cof_id int unsigned not null auto_increment primary key,

  # id to commit table
  cof_com_id int unsigned not null,

  # filename, i.e htdocs/index.php for gitlab/github
  # basename in CVS, SVN(trac)
  # TODO, let CVS and SVN also include directory name here
  cof_filename varchar(255) NOT NULL DEFAULT '',

  # flag whether file was added, modified or removed
  cof_added bool not null default false,
  cof_modified bool not null default false,
  cof_removed bool not null default false,

  # file versions. relevant to CVS only
  # the other repositories do not have per file revisions
  cof_old_version varchar(40) DEFAULT NULL,
  cof_new_version varchar(40) DEFAULT NULL
) ENGINE=MYISAM DEFAULT CHARSET=utf8;

# commit to issue relation (old issue_checkin table)
create table {{%issue_commit}} (
  isc_id int unsigned not null auto_increment primary key,
  # issue id
  isc_iss_id int unsigned not null,
  # commit id
  isc_com_id int unsigned not null
) ENGINE=MYISAM DEFAULT CHARSET=utf8;
