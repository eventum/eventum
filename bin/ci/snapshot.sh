#!/bin/sh
# create snapshot release from current HEAD
# travis will build release tarball and upload it to github release page
# https://github.com/eventum/eventum/releases/tag/snapshot

set -xe

git for-each-ref refs/tags/snapshot --format '%(refname:strip=2)' | xargs git tag -d

branch=$(git rev-parse --abbrev-ref HEAD)
commit=$(git rev-parse --short HEAD)
message="snapshot from $branch branch

created from $commit on $branch branch.
uploaded by travis. use at your own risk"

git tag -am "$message" snapshot HEAD
git push -f git@github.com:eventum/eventum.git snapshot
