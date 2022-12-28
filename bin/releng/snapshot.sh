#!/bin/sh
# create snapshot release from current HEAD
# https://github.com/eventum/eventum/releases/tag/snapshot

set -eu

repo_url=git@github.com:eventum/eventum.git
repo=eventum/eventum

die() {
	echo >&2 "ERROR: $*"
	exit 1
}

# create commit message
# assumes few variables being set
get_commit_message() {
	local filename=eventum-${version#v}.tar.xz
	local tarball=https://github.com/eventum/eventum/releases/download/snapshot/$filename
	cat <<-EOF
	A snapshot from $branch branch

	Created on $date from $commit on **$branch** branch.

	$shortlog

	Uploaded from GitHub Action. Use at your own risk.

	If the snapshot tarball ([$filename]($tarball)) is not appearing here,
	check Actions tab for errors: https://github.com/eventum/eventum/actions
	EOF
}

create_snapshot_tag() {
	local version branch commit date message
	git for-each-ref refs/tags/snapshot --format '%(refname:strip=2)' | xargs git tag -d
	git fetch --no-tags "$repo_url" "+refs/tags/v*:refs/tags/v*"

	version=$(git describe --tags --abbrev=9 HEAD)
	branch=$(git rev-parse --abbrev-ref HEAD)
	commit=$(git rev-parse --short=9 HEAD)
	shortlog=$(git show -s --format=%B HEAD | sed -e 's;^;    ;')
	date=$(LC_ALL=C TZ=UTC date)
	message=$(get_commit_message)

	git tag -am "$message" snapshot HEAD
	git push -f "$repo_url" snapshot
	git tag -d snapshot
}

create_snapshot_tag
