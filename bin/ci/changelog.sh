#!/bin/sh
# Update Release notes using Chandler
set -e

if [ "$TRAVIS_TAG" = "snapshot" ]; then
	exit 0
fi

gem install --no-ri --no-rdoc chandler

# Create token in https://github.com/settings/tokens with 'public_repo' access
# and set as env var:
# $ travis env set -p CHANDLER_GITHUB_API_TOKEN ...
chandler push --changelog=ChangeLog.md $TRAVIS_TAG
