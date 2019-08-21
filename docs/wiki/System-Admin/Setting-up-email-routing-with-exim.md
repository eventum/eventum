### Setting up email routing with exim

This describes how to set up the [Email Routing Interface](Email-Routing-Interface.md) with the Exim MTA.

## Address Extensions

Address extensions can be enabled with the local_part_suffix option in the Exim configuration file. If you were to e.g. route mails of the form "issue XXXX" and "issue" to the users eventum and archive on your system add this line to /etc/aliases

`issue: eventum archive`

and these two lines to the aliases router section of the Exim configuration file:

`system_aliases:`
`...`
`local_part_suffix_optional`
`local_part_suffix = *`
