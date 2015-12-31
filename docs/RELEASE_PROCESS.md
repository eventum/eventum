Notes about creating Eventum Release
====================================

Test before release
-------------------

- install yourself lowest supported php and it's modules (5.3 as of now) as
  depending on php version different PEAR modules may be installed
- create release tarball, unpack and run setup
`$ make dist`
- make sure upgrade/drop.sql lists all created tables
`$ sed -e 's,{{%\([^}]*\)}},eventum_\1,' upgrade/drop.sql`
- install twice to same database, second time select drop tables, install must not fail
if it fails the error is something like `DB Error: already exists`
- Test the new release directory with a quick installation
  * see if a new issue can be created correctly and etc
  * see that tables created are also in upgrade/drop.sql
- update translation keywords to launchpad
this should be done day before release so launchpad cron would update .po files.

Release process
---------------

- Update the ChangeLog.md file with the correct version number and release date

- create git tag
`$ git tag -s v3.0.7`

- build tarball again
`$ make dist`

- if all well, push out the tag
`$ git push --tags`

- go to github releases page, edit the new tag
- fill release title and release notes
- upload tarball and signature to the release
- to create a digital signature, use the following command:
`% gpg --armor --sign --detach-sig eventum-3.0.4.tar.gz`
- create tags also in scm and wiki submodules

After release
-------------

- update release number in init.php to indicate next dev version (APP_VERSION)
- start new version entry in Changelog.md
- add new milestone in github. just fill version number in Title field https://github.com/eventum/eventum/milestones
- move open tickets/pull requests to new milestone
- close old milestone
