### SCM integration

## Overview

Eventum's SCM integration feature links the commits in a Software Configuration Management system (CVS and Subversion are supported) to issues in Eventum. This is accomplished through the use of scripts that run on the SCM servers when commits occur, which parse the comment looking for references to issues, and notify Eventum when these are found. From the Eventum side, links to pages served by a third-party repository viewer are provided.

## Eventum Setup

This feature is located in the "General Setup" section of the Administration area.

The two fields are to enter URL patterns used to link the change to a separate SCM browser site. The parameters are as follows:

-   `{MODULE}`, the CVS module, or the Subversion project (the last component of the path to the repository)
-   `{FILE}`, the name of the file,
-   `{NEW_VERSION}`, the identifier of the new version
-   `{OLD_VERSION}`, the identifier of the old version

## CVS Setup

Setup instructions for CVS can be found in the Eventum help, in the "SCM Integration" section (Click "Help Topics" to see the table of contents.)

## Subversion Setup

Subversion Setup is described [here](Subversion-integration.md).

---

#### From General Setup

### SCM Integration

This feature allows your software development teams to integrate your Source Control Management system with your Issue Tracking System.

The integration is implemented in such a way that it will be forward compatible with pretty much any SCM system, such as CVS. When entering the required information for the checkout page and diff page input fields, use the following placeholders:

-   `{MODULE}` - The CVS module name
-   `{FILE}` - The filename that was committed
-   `{OLD_VERSION}` - The old revision of the file
-   `{NEW_VERSION}` - The new revision of the file

Further information can be found in Eventum Internal Help.

You should add to your `CVSROOT/loginfo` catchall entry:

for older CVS (1.11):

    # process any message with Eventum
    ALL /path/to/eventum-cvs-hook $USER %{sVv}

for newer CVS (1.12+):

    # process any message with Eventum
    ALL /path/to/eventum-cvs-hook $USER "%p" %{sVv}

for CVS 1.12+, you need at least r4452
