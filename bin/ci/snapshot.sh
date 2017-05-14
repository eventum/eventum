#!/bin/sh
# create snapshot release from current HEAD
# travis will build release tarball and upload it to github release page
# https://github.com/eventum/eventum/releases/tag/snapshot

set -e

git tag -d snapshot || :

version=$(git describe --tags HEAD)
branch=$(git rev-parse --abbrev-ref HEAD)
commit=$(git rev-parse --short HEAD)
message="snapshot from $branch branch

Created from $commit on $branch branch.
Uploaded by Travis. Use at your own risk.

If the snapshot tarball (eventum-${version#v}.tar.gz) is not appearing here,
check Travis CI for errors: https://travis-ci.org/eventum/eventum
"

git tag -am "$message" snapshot HEAD
git push -f git@github.com:eventum/eventum.git snapshot
