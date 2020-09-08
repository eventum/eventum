# Installation Instructions

The `eventum-cvs-hook` script, which is available in the `scm` sub-directory in
your Eventum installation directory, will need to be installed in your CVSROOT
CVS module by following the procedure below:

The first thing to do is to checkout the `CVSROOT` module from your CVS
repository:

```
$ cvs -d %repository_path% checkout CVSROOT
```

The command above will checkout and create the CVSROOT directory that you will
need to work with. Next, open the **loginfo** file and add the following line:

```
ALL /usr/bin/php %repository_path%/CVSROOT/eventum-cvs-hook $USER %{sVv}
```

Replace `%repository path%` by the appropriate absolute path in your CVS server,
such as `/home/username/repository` for instance. Also make sure to put the
appropriate path to your PHP binary.

You may also turn the parsing of commit messages for just a single CVS module by
substituting the `ALL` in the line above to the appropriate CVS module name, as
in:

```
%cvs module name% /usr/bin/php %repository_path%/CVSROOT/eventum-cvs-hook $USER %{sVv}
```

The last step of this installation process is to login into the CVS server and
copy the `eventum-cvs-hook` script into the `CVSROOT` directory. Make sure you
give the appropriate permissions to the script.
