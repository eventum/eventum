Notes about creating Eventum Release
====================================

Test before release
-------------------

- create release tarball, unpack and run setup
`$ make dist`
- make sure upgrade/drop.sql lists all created tables
`$ sed -e 's,{{%\([^}]*\)}},eventum_\1,' upgrade/drop.sql`
- install twice to same database, second time select drop tables, install must not fail
- Test the new release directory with a quick installation
  * see if a new issue can be created correctly and etc
  * see that tables created are also in upgrade/drop.sql
- update translation keywords to launchpad
this should be done day before release so launchpad cron would update .po files.

Release process
---------------

- Update the ChangeLog.md file with the correct version number and release date

- create git tag
`$ git tag -s v3.0.3`

- build tarball again
`$ make dist`

- if all well, push out the tag
`$ git push --tags`

- go to github releases page, edit the new tag
- fill release title and release notes
- upload tarball to the release

After release
-------------

- [add news entry](https://launchpad.net/eventum/+announce) to launchpad page
- update release number in init.php (APP_VERSION)
- start new version entry in Changelog.md
