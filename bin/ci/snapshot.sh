#!/bin/sh
# create snapshot release from current HEAD
# travis will build release tarball and upload it to github release page
# https://github.com/eventum/eventum/releases/tag/snapshot

set -e

have() {
	type -p "$1" >/dev/null 2>&1
}

# create commit message
# assumes few variables being set
get_commit_message() {
	cat <<-EOF
	snapshot from $branch branch

	Created on $date from $commit on $branch branch.
	Uploaded by Travis. Use at your own risk.

	If the snapshot tarball (eventum-${version#v}.tar.gz) is not appearing here,
	check Travis CI for errors: https://travis-ci.org/eventum/eventum
	EOF
}

create_snapshot_tag() {
	local version branch commit date message
	git tag -d snapshot || :

	version=$(git describe --tags --abbrev=8 HEAD)
	branch=$(git rev-parse --abbrev-ref HEAD)
	commit=$(git rev-parse --short=8 HEAD)
	date=$(LC_ALL=C TZ=UTC date)
	message=$(get_commit_message)

	git tag -am "$message" snapshot HEAD
	git push -f git@github.com:eventum/eventum.git snapshot
	git tag -d snapshot
}

# suggest to install travis cli tool
travis_help() {
	cat <<-EOF

	Install travis cli tool to follow log of the build:

	$ gem install travis
	EOF
}

# show build log of travis build
travis_log() {
	local branch="snapshot" job_id=6 sleep="20"
	local out status build_id

	printf "travis: showing build progress, ctrl+c to abort\n"
	printf "travis: sleeping $sleep seconds to wait for build to start\n"
	sleep $sleep
	printf "travis: figuring out build id... "
	out=$(travis branches)
	status=$(echo "$out" | grep "^$branch:" | head -n1)
	build_id=$(echo "$status" | sed -rne "s/^$branch:\s+#([0-9]+).+/\1/p")
	echo "$build_id"
	echo "$status"

	printf "travis: showing logs for #$build_id.$job_id\n"
	travis logs $build_id.$job_id
}

create_snapshot_tag

if have travis; then
	travis_log
else
	travis_help
fi
