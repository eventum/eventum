#!/bin/sh
#
# install tools neccessary for CI
#

cachedir=$(pwd)/cache

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

install_phing() {
	phing -version && return 0

	pear channel-discover pear.phing.info
	pear install phing/phing
	phpenv rehash
	phing -version
}

install_phing

get php-cs-fixer.phar
get phpcompatinfo.phar
get box.phar
