#!/bin/sh
set -e
set -x
app=eventum
rc=dev # development version
#rc=RC1 # release candidate
#rc= # release
dir=$app
podir=po

find_prog() {
	set +x
	local c prog=$1
	names="./$prog.phar $prog.phar $prog"
	prog=
	for c in $names; do
		prog=$(which $c) || continue
		prog=$(readlink -f "$prog")
		break
	done

	${prog:-false} --version >&2

	echo ${prog:-false}
}

# update timestamps from last commit
# see http://stackoverflow.com/a/5531813
update_timestamps() {
	set +x
	echo "Updating timestamps from last commit of each file, please wait..."
	git ls-files | while read file; do
		# skip files which were not exported
		test -f "$dir/$file" || continue
		rev=$(git rev-list -n 1 HEAD "$file")
		file_time=$(git show --pretty=format:%ai --abbrev-commit $rev | head -n 1)
		touch -d "$file_time" "$dir/$file"
	done
}

composer=$(find_prog composer)
box=$(find_prog box)

# checkout
rm -rf $dir
install -d $dir
dir=$(readlink -f $dir)

# setup submodules
git submodule init
git submodule update

git archive HEAD | tar -x -C $dir
# include submodules
# see http://stackoverflow.com/a/16843717
dir=$dir git submodule foreach 'cd $toplevel/$path && git archive HEAD | tar -x -C $dir/$path/'

update_timestamps

# checkout localizations from launchpad
if [ -d $podir ]; then
  cd $podir
  bzr pull
  cd -
else
  bzr branch lp:~glen666/eventum/po $podir
fi
rm -f $dir/localization/*.po
cp -af $podir/localization/*.po $dir/localization

# tidy up
cd $dir
version=$(awk -F"'" '/APP_VERSION/{print $4}' init.php)

if [ "$rc" = "dev" ]; then
	version=$(git describe --tags)
	# not good tags, try trimming
	version=$(echo "$version" | sed -e 's,release-,,; s/-final$//; s/^v//; ')

	sed -i -e "
		/define('APP_VERSION'/ {
			idefine('APP_VERSION', '$version');
		    d

		}" init.php
fi

# setup composer deps
if [ -n "$composer" ]; then
	# composer hack, see .travis.yml
	sed -i -e 's#pear/#pear-pear.php.net/#' composer.json
	$composer install --prefer-dist --no-dev --ignore-platform-reqs
	$composer licenses --no-dev --no-ansi >> docs/DEPENDENCIES.md
fi

# update to include checksums of js/css files
./bin/dyncontent-chksum.pl

# remove bundled deps
if [ -n "$composer" ]; then
	rm -r lib/{Smarty,pear,php-gettext,sphinxapi}
	rm composer.lock
	# cleanup vendors
	rm -r vendor/php-gettext/php-gettext/{tests,examples}
	rm -f vendor/php-gettext/php-gettext/[A-Z]*
	rm -r vendor/bin
	rm -r vendor/pear-pear.php.net/PEAR/bin

	# smarty: use -f, as dist and src packages differ
	# smarty src
	rm -rf vendor/smarty/smarty/{.svn,development,documentation,distribution/demo}
	rm -f vendor/smarty/smarty/distribution/{[A-Z]*,*.{txt,json}}
	# smarty dist
	rm -rf vendor/smarty/smarty/demo
	rm -f vendor/smarty/smarty/{[A-Z]*,*.txt}

	rm vendor/composer/*.json

	# component related deps, not needed runtime
	rm -r vendor/symfony/process
	rm -r vendor/kriswallsmith/assetic
	rm -r vendor/robloach/component-installer
	rm -r vendor/components
	rm -r vendor/malsup/form
	rm -r vendor/enyo/dropzone
	install -d vendor/kriswallsmith/assetic/src
	touch vendor/kriswallsmith/assetic/src/functions.php
	# cleanup components
	rm htdocs/components/*/*-built.js
	rm htdocs/components/*-built.js
	rm htdocs/components/jquery-ui/*.js
	rm htdocs/components/require.*
	mv htdocs/components/jquery-ui/themes/{base,.base}
	rm -r htdocs/components/jquery-ui/themes/*
	mv htdocs/components/jquery-ui/themes/{.base,base}
	rm -r htdocs/components/jquery-ui/ui/minified
	rm -r htdocs/components/jquery-ui/ui/i18n
	rm htdocs/components/dropzone/index.js

	# and old code in repo
	rm -r htdocs/js/jquery

	# this will do clean pear in vendor dir
	touch pear.download pear.install pear.clean
	./bin/update-pear.sh
	rm pear.download pear.install pear.clean

	# eventum standalone cli
	make -C cli eventum.phar composer=$composer box=$box
	# eventum scm
	make -C scm phar box=$box
fi

make -C localization install clean
install -d logs templates_c locks htdocs/customer
touch logs/{cli.log,errors.log,irc_bot.log,login_attempts.log}
chmod -R a+rX .
chmod -R a+rwX templates_c locks logs config
# clean these now, can't omit them from git export as needed in release preparation process
rm -f composer.json bin/{dyncontent-chksum.pl,update-pear.sh}
rm -f cli/{composer.json,box.json.dist,Makefile}

# sanity check
if [ "$rc" != "dev" ]; then
	find -name '*.php' | xargs -l1 php -n -l
fi

cd -

if [ "$rc" = "dev" ]; then
	rc=
fi

# make tarball and md5 checksum
rm -rf $app-$version
mv $dir $app-$version
tar --owner=root --group=root -czf $app-$version$rc.tar.gz $app-$version
rm -rf $app-$version
md5sum -b $app-$version$rc.tar.gz > $app-$version$rc.tar.gz.md5
chmod a+r $app-$version$rc.tar.gz $app-$version$rc.tar.gz.md5

if [ -x /usr/bin/gpg ] && [ "$(gpg --list-keys | wc -l)" -gt 0 ]; then
	gpg --armor --sign --detach-sig $app-$version$rc.tar.gz
else
	cat <<-EOF

	To create a digital signature, use the following command:
	% gpg --armor --sign --detach-sig $app-$version$rc.tar.gz

	This command will create $app-$version$rc.tar.gz.asc
	EOF
fi

if [ -x dropin ]; then
	./dropin $app-$version$rc.tar.gz $app-$version$rc.tar.gz.md5
fi
