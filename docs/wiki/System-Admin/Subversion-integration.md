### Subversion integration

## Overview

This describes how to set up [SCM integration](SCM-integration.md) for Subversion.

## Setup

The integration consists of a script that is run on the subversion (svn) server after each commit. Thus, command-line PHP must be available on the subversion server.

### Obtain script

The script is located in your Eventum installation here: /path-to-eventum/scm/eventum-svn-hook

You may also want the most recent copy: <http://bazaar.launchpad.net/~eventum-developers/eventum/trunk/annotate/head:/scm/eventum-svn-hook>

This file must be copied to your subversion server.

### Installation

Now that you have the script, you must copy it into the hooks directory in your subversion repository, so that it is run after each commit.

Assuming that your svn path is "/home/svn" and the repository you want to monitor is called "projects", then you will need to copy the file above to "/home/svn/projects/hooks/post-commit". If the post-commit script already exists, then svn is already integrating with some other software, and you will have to write a new script that calls both the existing script and the new script. See the subversion documentation for more details.

### Configuration

After you have copied it, make sure you edit the variable near the top of the script to point to your Eventum installation. The script pings your Eventum installation over HTTP (or HTTPS).
