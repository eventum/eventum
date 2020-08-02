#!/bin/sh
#
# install tools necessary for CI
#

cachedir=$(pwd)/cache
install -d $cachedir

# copy tool from cache
# or download and put it to cache
get() {
	local tool=$1
	local cachefile=$cachedir/$tool
	if [ -e $cachefile ]; then
		cp -p $cachefile .
	else
		make $tool
		cp -p $tool $cachedir
	fi
}

get php-cs-fixer.phar
get phpcompatinfo.phar
get box.phar
get phing.phar
