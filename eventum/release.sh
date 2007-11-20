#!/bin/sh
set -e
set -x
app=eventum
svn=svn://eventum.mysql.org/eventum-gpl/trunk/eventum
dir=$app

# checkout
rm -rf $dir
svn export $svn $dir

# tidy up
cd $dir
version=$(awk -F"'" '/APP_VERSION/{print $4}' init.php)
make -C misc/localization
chmod -R a+rwX templates_c locks logs config
rm -f release.sh
cd -

# make tarball and md5 checksum
rm -rf $app-$version
mv $dir $app-$version
tar -czf $app-$version.tar.gz $app-$version
rm -rf $app-$version
md5sum -b $app-$version.tar.gz > $app-$version.tar.gz.md5
