#!/bin/sh
#
# install tools necessary for CI
#

destdir="$1"

# copy tool from cache
# or download and put it to cache
get() {
	local tool="$1"

	make $tool
	cp -p $tool $destdir
}

get php-cs-fixer.phar
get phpcompatinfo.phar
get box.phar
get phing.phar
