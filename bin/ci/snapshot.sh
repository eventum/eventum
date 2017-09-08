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

travis_build_id() {
	local branch="$1" out ids

	out=$(travis history -cdb "$branch" -l 10)
	# 2017-09-09 01:18:33 #3305 started: snapshot Elan Ruusamäe snapshot: follow travis logs if possible
	echo "$out" | sed -rne 's/.+#([0-9]+) started:.+/\1/p' | head -n 1
}

# show build log of travis build
travis_log() {
	# ".6" is the "deploy" job
	local branch="snapshot" job_id=6 sleep="20"
	local out status build_id

	printf "travis: showing build progress, ctrl+c to abort\n"
	printf "travis: sleeping $sleep seconds to wait for build to start\n"
	sleep $sleep

	printf "travis: figuring out build id... "
	build_id=$(travis_build_id "$branch")
	printf "#$build_id\n"

	printf "travis: showing logs for #$build_id.$job_id\n"
	travis logs $build_id.$job_id
}

create_snapshot_tag

if have travis; then
	travis_log
else
	travis_help
fi
