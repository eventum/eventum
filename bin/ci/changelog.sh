#!/bin/sh
# Update Release notes using Chandler
set -e

# create fake changelog file for "snapshot" tag
create_snapshot_changelog() {
	# obtain current tag message
	# https://stackoverflow.com/a/26132640/2314626
	object=$(git rev-parse snapshot)

	message=$(git cat-file -p $object)
	message=$(echo "$message" | tail -n +6)
	date=$(LC_ALL=C TZ=UTC date)
	version=$(git describe --tags --abbrev=8 HEAD)

	# Extra Travis env variables:
	# https://docs.travis-ci.com/user/environment-variables/#Default-Environment-Variables

	cat <<-EOF
## $TRAVIS_TAG

$message

Built by #$TRAVIS_BUILD_NUMBER
Build finished at $date
https://travis-ci.org/eventum/eventum/builds/$TRAVIS_BUILD_ID

	EOF
}

if [ "$TRAVIS_TAG" = "snapshot" ]; then
	create_snapshot_changelog > CHANGELOG.md
fi

gem install --no-ri --no-rdoc chandler

# Create token in https://github.com/settings/tokens with 'public_repo' access
# and set as env var:
# $ travis env set -p CHANDLER_GITHUB_API_TOKEN ...
chandler push $TRAVIS_TAG
