#!/bin/sh
url=$(svn info | awk '/^URL:/{print $2}')
rev=$(svn info | awk '/^Revision:/{print $2}')

version=$(awk -F"'" '/APP_VERSION/{print $4}' init.php)
tag="eventum-$version"
tagurl=${url%/trunk/eventum}/tags/$tag

echo "Making tag: $tag at revision $rev"
echo svn cp $url $tagurl
echo ""
echo "Press ENTER to continue..."
read a

svn cp $url $tagurl
