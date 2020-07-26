#!/bin/bash
set -e
set -x
app=eventum
dir=$app
podir=po
topdir=$(pwd)

quick=${QUICK-false}

find_prog() {
	set +x
	local c version prog=$1

	case "$prog" in
	phing)
		version="-version"
		;;
	*)
		version="--version"
		;;
	esac

	names="./$prog.phar $prog.phar $prog"
	prog=
	for c in $names; do
		prog=$(which $c) || continue
		prog=$(readlink -f "$prog")
		break
	done

	${prog:-false} $version >&2

	echo ${prog:-false}
}

# update timestamps from last commit
# see http://stackoverflow.com/a/5531813
update_timestamps() {
	set +x
	echo >&2 "Updating timestamps from last commit of each file in ${dir#$topdir/}, please wait..."
	git ls-files | while read file; do
		# skip files which were not exported
		test -f "$dir/$file" || continue
		rev=$(git rev-list -n 1 HEAD "$file")
		file_time=$(git show --pretty=format:%ai --abbrev-commit $rev | head -n 1)
		touch -d "$file_time" "$dir/$file"
	done
}

vcs_checkout() {
	set -x
	local submodule dir=$dir absdir

	rm -rf $dir
	install -d $dir
	absdir=$(readlink -f $dir)

	# setup submodules
	git submodule init
	git submodule update

	git archive HEAD | tar -x -C $dir

	$quick && return

	# include submodules
	# see http://stackoverflow.com/a/16843717
	dir=$absdir git submodule foreach 'cd $toplevel/$path && git archive HEAD | tar -x -C $dir/$path/'

	update_timestamps

	local submodule
	for submodule in $(git submodule -q foreach 'echo $path'); do
		cd $submodule
		dir=$absdir/$submodule update_timestamps
		cd $topdir
	done
}

po_checkout() {
	echo >&2 "Checkout localizations from launchpad"
	if [ -d $podir ]; then
	  cd $podir
	  $quick || bzr pull
	  cd ..
	else
	  bzr branch lp:~glen666/eventum/po $podir
	fi
	rm -f $dir/localization/*.po
	cp -af $podir/localization/*.po $dir/localization
	make -C $dir/localization touch-po
}

# setup $version
set_version() {
	version=$(git describe --tags --abbrev=9 HEAD)
	# trim 'v' prefix
	version=${version#v}
}

# clean trailing spaces/tabs
clean_whitespace() {
	sed -i -e 's/[\t ]\+$//' "$@"
}

install_dependencies() {
	echo >&2 "Setup composer deps"

	$composer install --prefer-dist --no-dev --no-suggest

	# clean distribution and dump autoloader again
	clean_vendor
	# call "composer install" to workaround for flex not placing the version check
	$composer install --prefer-dist --no-dev --no-suggest
	$composer dump-autoload
}

install_assets() {
	echo >&2 "Install assets"
	yarn
	yarn assets:production
}

dependencies_report() {
	$quick && return
	echo >&2 "Create dependencies report"

	# save dependencies information
	$composer licenses --no-dev --no-ansi > deps
	# avoid composer warning in resulting doc file
	grep Warning: deps && exit 1
	clean_whitespace deps
	cat deps >> docs/DEPENDENCIES.md && rm deps
}

phpcompatinfo_report() {
	$quick && return
	echo >&2 "Create phpcompatinfo report"

	cp $topdir/phpcompatinfo.json .
	$phpcompatinfo analyser:run --alias current --output docs/PhpCompatInfo.txt
	rm phpcompatinfo.json

	# avoid empty result
	grep 'None data source matching' docs/PhpCompatInfo.txt && exit 1

	clean_whitespace docs/PhpCompatInfo.txt
}

# common cleanups:
# - remove closing php tag
# - strip trailing whitespace
# - use unix newlines
clean_scripts() {
	# here's shell oneliner to remove ?> from all files which have it on their last line:
	find -name '*.php' | xargs -r sed -i -e '${/^?>$/d}'
	# sometimes if you are hit by this problem, you need to kill last empty line first:
	find -name '*.php' | xargs -r sed -i -e '${/^$/d}'
	# and as well can remove trailing spaces/tabs:
	find -name '*.php' | xargs -r sed -i -e 's/[\t ]\+$//'
	# remove DOS EOL
	find -name '*.php' | xargs -r sed -i -e 's,\r$,,'
}

clean_vendor() {
	echo >&2 "Cleanup vendor of unwanted files"
	$phing -f $topdir/build.xml clean-vendor

	# Clean empty dirs
	find vendor -type d -print0 | sort -zr | xargs -0 rmdir --ignore-fail-on-non-empty

	cd vendor
	clean_scripts
	cd ..
}

clean_dist() {
	echo >&2 "Cleanup distribution of unwanted files"
	$phing -f $topdir/build.xml clean-dist
}

cleanup_postdist() {
	rm composer.json
	rm htdocs/debugbar

	# cleanup vendors
	rm vendor/composer/*.json
	rm vendor/*/LICENSE
	rm composer.lock

	# node related
	rm package.json
	rm webpack.mix.js
	rm yarn.lock
}

phplint() {
	$quick && return

	echo "Running php lint on source files using $(php --version | head -n1)"
	$phing -f $topdir/build.xml phplint
	rm .phplint.cache
}

# make tarball and md5 checksum
make_tarball() {
	rm -rf $app-$version
	mv $dir $app-$version
	tar --owner=root --group=root -cJf $app-$version.tar.xz $app-$version
	rm -rf $app-$version
	md5sum -b $app-$version.tar.xz > $app-$version.tar.xz.md5
	chmod a+r $app-$version.tar.xz $app-$version.tar.xz.md5
}

sign_tarball() {
	local manual=0
	if [ -x /usr/bin/gpg ] && [ "$(gpg --list-keys | wc -l)" -gt 0 ]; then
		gpg --armor --sign --detach-sig $app-$version.tar.xz || manual=1
	else
		manual=1
	fi

	# show manual instructions
	if [ "$manual" ]; then
		cat <<-EOF

		To create a digital signature, use the following command:
		% gpg --armor --sign --detach-sig $app-$version.tar.xz

		This command will create $app-$version.tar.xz.asc
		EOF
	fi
}

upload_tarball() {
	[ -x dropin ] || return 0

	./dropin $app-$version.tar.xz $app-$version.tar.xz.md5
}

prepare_source() {
	# add dirs for customization
	install -d config/{workflow,custom_field,templates,crm,partner,include}

	install_dependencies
	install_assets
	dependencies_report
	phpcompatinfo_report

	# setup localization
	make -C localization install clean

	# install dirs and fix permissions
	install -d var/{log,cache,lock}
	touch var/log/{eventum.log,auth.log,cli.log,errors.log,login_attempts.log}
	touch config/{private_key.php,secret_key.php,setup.php}
	chmod -R a+rX .
	chmod -R a+rwX config var

	# cleanup rest of the stuff, that was necessary for release preparation process
	clean_dist
	cleanup_postdist

	phplint
}

# download tools
make phpcompatinfo.phar phing.phar

composer=$(find_prog composer)
phpcompatinfo=$(find_prog phpcompatinfo)
phing=$(find_prog phing)

# checkout
vcs_checkout
po_checkout

# tidy up
cd $dir
	prepare_source
cd ..

set_version
make_tarball
sign_tarball
upload_tarball
