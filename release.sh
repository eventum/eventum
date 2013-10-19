#!/bin/sh
set -e
set -x
app=eventum
rc=dev # development version
#rc=RC1 # release candidate
#rc= # release
dir=$app

# checkout
rm -rf $dir
install -d $dir

git archive master | tar -x -C $dir

# update timestamps from last commit
# http://stackoverflow.com/questions/1964470/whats-the-equivalent-of-use-commit-times-for-git/5531813#5531813
update_timestamps() {
	set +x
	echo "Updating timestamps from last commit, please wait..."
	git ls-files | while read file; do
		rev=$(git rev-list -n 1 HEAD "$file")
		file_time=$(git show --pretty=format:%ai --abbrev-commit $rev | head -n 1)
		touch -d "$file_time" "$dir/$file"
	done
}
update_timestamps

# checkout localizations from launchpad
# if running in bzr checkout, clone that instead
if [ -d lp ]; then
  cd lp
  bzr pull
  cd ..
else
  bzr clone lp:eventum lp
fi
rm -f $dir/localization/*.po
cp -af lp/localization/*.po $dir/localization

# tidy up
cd $dir
version=$(awk -F"'" '/APP_VERSION/{print $4}' init.php)

if [ "$rc" = "dev" ]; then
	version=$(git describe --tags)
	# not good tags, try trimming
	version=$(echo "$version" | sed -e 's,release-,,; s/-final//')

	sed -i -e "
		/define('APP_VERSION'/ {
			idefine('APP_VERSION', '$version');
		    d

		}" init.php
fi

# update to include checksums of js/css files
./dyncontent-chksum.pl

make -C localization install localedir=.
rm -f localization/{tsmarty2c,*.mo}
install -d logs templates_c locks htdocs/customer
touch logs/{cli.log,errors.log,irc_bot.log,login_attempts.log}
chmod -R a+rX .
chmod -R a+rwX templates_c locks logs config
rm -f release.sh update-pear.sh phpxref.cfg phpxref.sh dyncontent-chksum.pl phpcs.xml build.xml pear.xml
rm -f .editorconfig .gitignore .bzrignore composer.json
rm -rf tests

# sanity check
if [ "$rc" != "dev" ]; then
	find -name '*.php' | xargs -l1 php -n -l
fi

rm -rf .bzr*
cd -

# make tarball and md5 checksum
rm -rf $app-$version
mv $dir $app-$version
tar --owner=root --group=root -czf $app-$version.tar.gz $app-$version
rm -rf $app-$version
md5sum -b $app-$version.tar.gz > $app-$version.tar.gz.md5
chmod a+r $app-$version.tar.gz $app-$version.tar.gz.md5

if [ -x dropin ]; then
	./dropin $app-$version.tar.gz $app-$version.tar.gz.md5
fi
