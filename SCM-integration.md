## Overview ##

Eventum's SCM integration feature links the commits in a Software Configuration Management system (CVS and Subversion are supported) to issues in Eventum. This is accomplished through the use of scripts that run on the SCM servers when commits occur, which parse the comment looking for references to issues, and notify Eventum when these are found. From the Eventum side, links to pages served by a third-party repository viewer are provided.

Eventum Setup
-------------

This feature is located in the "General Setup" section of the Administration area.

The two fields are to enter URL patterns used to link the change to a separate SCM browser site. The parameters are as follows:

-   `{MODULE}`, the CVS module, or the Subversion project (the last component of the path to the repository)
-   `{FILE}`, the name of the file,
-   `{NEW_VERSION}`, the identifier of the new version
-   `{OLD_VERSION}`, the identifier of the old version

CVS Setup
---------

Setup instructions for CVS can be found in the Eventum help, in the "SCM Integration" section (Click "Help Topics" to see the table of contents.)

Subversion Setup
----------------

Subversion Setup is described [here](Subversion integration "wikilink").