#!/bin/sh
# Update Release notes using Chandler
set -e

# update changelog entry for "snapshot" release
upload_snapshot_changelog() {
	date=$(LC_ALL=C TZ=UTC date)
	version=$(git describe --tags --abbrev=8 HEAD)
	tarball=eventum-${version#v}.tar.gz
	url=https://github.com/$TRAVIS_REPO_SLUG/releases/download/$TRAVIS_TAG/$tarball

	# Info about Travis ENV variables:
	# https://docs.travis-ci.com/user/environment-variables/#Default-Environment-Variables
	notes=$(cat release_note.txt)
	title=$(echo "$notes" | head -n 1)
	notes=$(echo "$notes" | tail -n +3)
	notes=$(echo "$notes" | sed -e "s;$tarball;[$tarball]($url);")
	notes=$(cat <<-EOF
$notes

Build [#$TRAVIS_BUILD_NUMBER](https://travis-ci.org/$TRAVIS_REPO_SLUG/builds/$TRAVIS_BUILD_ID) finished at $date
Release tarball built from [#$TRAVIS_JOB_NUMBER](https://travis-ci.org/$TRAVIS_REPO_SLUG/jobs/$TRAVIS_JOB_ID)

	EOF
	)

	TRAVIS_REPO_SLUG=$TRAVIS_REPO_SLUG \
	TRAVIS_TAG=$TRAVIS_TAG \
	RELEASE_TITLE="$title" \
	RELEASE_NOTES="$notes" \
	bin/ci/tag-update.rb
}

gem install --no-ri --no-rdoc chandler

if [ "$TRAVIS_TAG" = "snapshot" ]; then
	upload_snapshot_changelog
	exit 0
fi

# Create token in https://github.com/settings/tokens with 'public_repo' access
# and set as env var:
# $ travis env set -p CHANDLER_GITHUB_API_TOKEN ...
chandler push $TRAVIS_TAG
