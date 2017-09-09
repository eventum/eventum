#!/bin/sh
set -xe

bin/ci/tools.sh
bin/ci/locales.sh

# need to fetch tags first for release process
git fetch --tags --unshallow

# drop 'snapshot' tag, so that tarball created from snapshot gets identified better
# but before that, store result for later use
if [ "$TRAVIS_TAG" = "snapshot" ]; then
	# obtain current tag message
	# https://stackoverflow.com/a/26132640/2314626
	object=$(git rev-parse $TRAVIS_TAG)
	message=$(git cat-file -p $object)
	echo "$message" | tail -n +6 > release_note.txt

	git tag -d $TRAVIS_TAG
fi

# prepare release tarball
make dist
