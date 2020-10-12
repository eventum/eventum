#!/bin/sh
# create snapshot release from current HEAD
# travis will build release tarball and upload it to github release page
# https://github.com/eventum/eventum/releases/tag/snapshot

set -e

repo_url=git@github.com:eventum/eventum.git
travis_opts="--no-interactive --skip-version-check --skip-completion-check"
repo=eventum/eventum

have() {
	type -p "$1" >/dev/null 2>&1
}

# create commit message
# assumes few variables being set
get_commit_message() {
	cat <<-EOF
	snapshot from $branch branch

	Created on $date from $commit on **$branch** branch.

	$shortlog

	Uploaded from Travis CI. Use at your own risk.

	If the snapshot tarball (eventum-${version#v}.tar.xz) is not appearing here,
	check Travis CI project for errors: https://travis-ci.org/eventum/eventum
	EOF
}

create_snapshot_tag() {
	local version branch commit date message
	git tag -d snapshot || :
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

travis_cmd() {
	local cmd="$1"; shift

	travis "$cmd" $travis_opts --repo "$repo" "$@" | tee -a .travis.log
}

# suggest to install travis cli tool
travis_help() {
	cat <<-EOF

	Install travis cli tool to follow log of the build:

	$ gem install travis
	EOF
}

travis_branch_build_id() {
	local branch="$1"

	# snapshot: #6057 started    Move travis options to environment, use long options
	travis_cmd branches | sed -rne "s/^$branch:.+#([0-9]+) (created|started|errored|passed|failed) .+/\1/p" | head -n1
}

# find last build id from specified branch
# needs to be status "started"
travis_build_id() {
	local branch="$1" out bid

	while [ -z "$bid" ]; do
		# sleep not to hammer, although the travis command itself is slow
		printf >&2 "."
		sleep 1

		bid=$(travis_branch_build_id "$branch")
		test -n "$bid" || continue
		test -n "$before_id" || break
		test "$bid" -gt "$before_id" && break
	done
	echo "$bid"
}

# show build log of travis build
travis_log() {
	# ".5" is the "deploy" job
	local branch="snapshot" job_id=5 build_id

	printf >&2 "travis: figuring out build id for $branch after #$before_id..."
	build_id=$(travis_build_id "$branch")
	printf >&2 " #$build_id\n"

	printf >&2 "travis: showing logs for #$build_id.$job_id\n"
	# ignore error from `travis logs`
	# https://github.com/travis-ci/travis.rb/issues/541
	# https://github.com/pusher-community/pusher-websocket-ruby/issues/51
	travis_cmd logs $build_id.$job_id || :
}

> .travis.log
have travis && before_id=$(travis_branch_build_id snapshot)

create_snapshot_tag

if have travis; then
	travis_log
else
	travis_help
fi
