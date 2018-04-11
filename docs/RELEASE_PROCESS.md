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

- Create PR to update composer dependencies. [example](https://github.com/eventum/eventum/pull/360)
- Make sure [src/Db/Table.php](src/Db/Table.php) lists all created tables in install process (even ones no longer used)
- Create and download snapshot tarball
- Install twice to same database, second time select drop tables, install must not fail
if it fails the error is something like `DB Error: already exists`
- Test the new release directory with a quick installation
  * see if a new issue can be created
  * test if you can upload attacment
  * ...
- update translation keywords to launchpad
  * Run `make pot`
  * Commit eventum.pot and push
  * this should be done day before release so launchpad cron would update .po files.

# Release process

- Update `CHANGELOG.md` file with the correct version number and release date

Do not forget to update changeset link to point to tag not master

- Update git submodule to point to master
```
git submodule update --init
cd docs/wiki
git fetch origin
git checkout master
cd ../..
```

Commit both changes
```
git commit -am 'prepare for 3.3.1 release'
```

- Create git tag
```
$ git tag -s v3.3.1 -m 'release v3.3.1'
$ git push origin v3.3.1

```
- wait for Travis-CI to build release tarball, download and test it again
- go to github releases page, edit the new tag
- fill release title and release notes
- upload tarball and signature to the release
- to create a digital signature, use the following command:
```
% gpg --armor --sign --detach-sig eventum-3.3.1.tar.gz
```
- create tag also in wiki submodule
```
cd docs/wiki
git tag v3.3.1
git push origin v3.3.1
```

# After release

- add `launchpad` named remote and configure it to push only `master` and named tags
```
$ git remote add launchpad git+ssh://glen666@git.launchpad.net/eventum
$ git config --add remote.launchpad.push refs/heads/master:refs/heads/master
$ git config --add remote.launchpad.push refs/tags/v*:refs/tags/v*
```
- publish changes also on launchpad git repo
```
$ git pull launchpad
$ git push launchpad
```
- add new milestone in github. just fill version number in Title field https://github.com/eventum/eventum/milestones
- move open tickets/pull requests to new milestone
- close old milestone
- verify that you did not forget to update wiki submodule
- update `VERSION` constant in `src/AppInfo.php` to indicate next dev version
- start new version entry in CHANGELOG.md
