#!/bin/sh
set -xeu

get_version() {
	local oldver newver version="${1:-}"

	if [ -n "$version" ]; then
		echo "$version"
		return
	fi

	oldver=$(git describe --tags --abbrev=0 --exclude=snapshot)
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

if [ "${1:-}" = "--amend" ]; then
	AMEND=true
	shift
else
	AMEND=false
fi

topdir=$(git rev-parse --show-toplevel)
VERSION=$(get_version "${1:-}")
RELDATE=$(date -u +%Y-%m-%d)
SIGN_KEY=$(git config --get user.signemail || git config --get user.email)
TAG=v$VERSION

cd $topdir
git fetch origin
git rebase origin/master

cache_gpg_askpass

patch_changelog "s/^## \[$(quote "$VERSION")\] *-* *$/& - $RELDATE/"
patch_changelog "/^\[$(quote "$VERSION")\]/ s/\.\.\.master/...$TAG/"

git commit -am "Prepare for $VERSION release"

if $AMEND; then
	echo "Entering shell to make changes before release"
	bash
fi

cd $topdir
GIT_COMMITTER_EMAIL=$SIGN_KEY git tag -s -u "$SIGN_KEY" "$TAG" -m "Release $TAG"

echo "Press ENTER to push upstreams"
read a

cd $topdir
git push origin master
git push origin $TAG
git push launchpad

cat <<EOF

Manage milestones:
- https://github.com/eventum/eventum/milestones

EOF
