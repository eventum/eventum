# Notes about creating Eventum Release

## Making release tarball

Eventum release tarball is created by Travis-CI for each commit and uploaded to
GitHub when the commit is associated with a tag.

This means that to make release just need to create named tag.

There's also special tag named `snapshot` used to create test tarball before actual release.

```
$ make snapshot
```

# Test before release

- Create and download snapshot tarball
- Make sure [src/Db/Table.php](src/Db/Table.php) lists all created tables in install process (even ones no longer used)
- install twice to same database, second time select drop tables, install must not fail
if it fails the error is something like `DB Error: already exists`
- Test the new release directory with a quick installation
  * see if a new issue can be created correctly and etc
- update translation keywords to launchpad
this should be done day before release so launchpad cron would update .po files.

Release process
---------------

- Update `ChangeLog.md` file with the correct version number and release date

Do not forget to update changeset link to point to tag not master

- Update git submodule to point to master
```
git submodule update
cd docs/wiki
git fetch origin
git checkout master
cd ../..
git commit -am 'updated wiki submodule'
```

- Create git tag
```
$ git tag -s v3.2.0 -m 'release v3.2.0'

```
- wait for Travis-CI to build release tarball, download and test it again
- go to github releases page, edit the new tag
- fill release title and release notes
- upload tarball and signature to the release
- to create a digital signature, use the following command:
```
% gpg --armor --sign --detach-sig eventum-3.2.0.tar.gz
```
- create tag also in wiki submodule
```
cd docs/wiki
git tag v3.2.0
git push origin v3.2.0
```

After release
-------------

- add `launchpad` named remote and configure it to push only `master` and named tags
```
$ git remote add launchpad git+ssh://glen666@git.launchpad.net/eventum
$ git config --add remote.launchpad.push refs/heads/master:refs/heads/master
$ git config --add remote.launchpad.push refs/tags/v*:refs/tags/v*
```
- publish changes also on launchpad git repo
```
$ git push launchpad
```
- add new milestone in github. just fill version number in Title field https://github.com/eventum/eventum/milestones
- move open tickets/pull requests to new milestone
- close old milestone
- verify that you did not forget to update wiki submodule
- update release number in init.php to indicate next dev version (`APP_VERSION`)
- start new version entry in ChangeLog.md
