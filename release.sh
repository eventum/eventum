#!/bin/sh
set -e
set -x
app=eventum
#rc=dev # development version
rc=RC1 # release candidate
#rc= # release
dir=$app

# checkout
rm -rf $dir

# if running in bzr checkout, clone that instead
if [ "$(bzr revno)" ]; then
	bzr clone . $dir
else
	bzr clone lp:eventum $dir
fi

# tidy up
cd $dir
version=$(awk -F"'" '/APP_VERSION/{print $4}' init.php)

if [ "$rc" = "dev" ]; then
	revno=$(bzr revno $dir)
	sed -i -e "
		/define('APP_VERSION'/ {
			idefine('APP_VERSION', '$version-bzr$revno');
		    d

		}" init.php
fi

# update to include checksums of js/css files
./dyncontent-chksum.pl

make -C localization install localedir=.
rm -f localization/{tsmarty2c,*.mo}
touch logs/{cli.log,errors.log,irc_bot.log,login_attempts.log}
chmod -R a+rX .
chmod -R a+rwX templates_c locks logs config
rm -f release.sh pear.sh phpxref.cfg phpxref.sh dyncontent-chksum.pl phpcs.xml build.xml 
rm -rf tests

# sanity check
if [ "$rc" != "dev" ]; then
	find -name '*.php' | xargs -l1 php -n -l
fi

rm -rf .bzr*
cd -

if [ "$rc" = "dev" ]; then
	rc=-dev-r$revno
fi

# make tarball and md5 checksum
rm -rf $app-$version
mv $dir $app-$version
tar -czf $app-$version$rc.tar.gz $app-$version
rm -rf $app-$version
md5sum -b $app-$version$rc.tar.gz > $app-$version$rc.tar.gz.md5
chmod a+r $app-$version$rc.tar.gz $app-$version$rc.tar.gz.md5

if [ -x dropin ]; then
	./dropin $app-$version$rc.tar.gz $app-$version$rc.tar.gz.md5
fi
