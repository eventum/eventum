# fix wrong iaf_filesize caused by mbstring overload
# (or some other technical error)
# https://github.com/eventum/eventum/pull/78

update {{%issue_attachment_file}}
    set iaf_filesize=length(iaf_file)
    where iaf_filesize!=length(iaf_file);
