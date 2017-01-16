#!/bin/sh
set -xe

test "$RELEASE" = "yes" || exit 0

bin/ci/tools.sh
bin/ci/locales.sh

# need to fetch tags first for release process
git fetch --tags --unshallow

# drop 'snapshot' tag, so that tarball created from snapshot gets identified better
git for-each-ref refs/tags/snapshot --format '%(refname:strip=2)' | xargs git tag -d

# prepare release tarball
make dist
