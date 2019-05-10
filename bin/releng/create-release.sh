#!/bin/sh
set -xeu

get_version() {
	local oldver newver version="${1:-}"

	if [ -n "$version" ]; then
		echo "$version"
		return
	fi

	oldver=$(git describe --tags --abbrev=0)
	oldver=${oldver#v}
	$topdir/contrib/shell-semver/increment_version.sh -p $oldver
}

# git doesn't askpass, so warm it up
cache_gpg_askpass() {
	gpg --clearsign --default-key "$SIGN_KEY" --output=/dev/null "$0" >/dev/null
}

quote() {
	echo "$*" | sed -e 's/[\.]/\\&/g'
}

patch_changelog() {
	local c1 c2

	c1=$(md5sum < CHANGELOG.md)
	sed -i -e "$@" CHANGELOG.md
	c2=$(md5sum < CHANGELOG.md)
	# changelog not modified. something wrong
	test "$c1" != "$c2"
}

topdir=$(git rev-parse --show-toplevel)
VERSION=$(get_version "${1:-}")
RELDATE=$(date -u +%Y-%m-%d)
SIGN_KEY=$(git config --get user.signemail || git config --get user.email)
TAG=v$VERSION

cd $topdir
git fetch origin
git rebase origin/master

cd $topdir/docs/wiki
git checkout master
git pull --rebase
cd ../..

cache_gpg_askpass

patch_changelog "s/^## \[$(quote "$VERSION")\] *-* *$/& - $RELDATE/"
patch_changelog "/^\[$(quote "$VERSION")\]/ s/\.\.\.master/...$TAG/"
git commit -am "prepare for $VERSION release"

cd $topdir/docs/wiki
git tag $TAG

cd $topdir
git tag -s -u "$SIGN_KEY" "$TAG" -m "release $TAG"

echo "Press ENTER to push upstreams"
read a

cd $topdir
git push origin master
git push origin $TAG
git push launchpad
cd $topdir/docs/wiki
git push origin $TAG
