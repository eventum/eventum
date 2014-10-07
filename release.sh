#!/bin/sh
set -e
set -x
app=eventum
rc=dev # development version
#rc=RC1 # release candidate
#rc= # release
dir=$app
podir=po

# checkout
rm -rf $dir
install -d $dir

git archive HEAD | tar -x -C $dir

# update timestamps from last commit
# http://stackoverflow.com/questions/1964470/whats-the-equivalent-of-use-commit-times-for-git/5531813#5531813
update_timestamps() {
	set +x
	echo "Updating timestamps from last commit, please wait..."
	git ls-files | while read file; do
		rev=$(git rev-list -n 1 HEAD "$file")
		file_time=$(git show --pretty=format:%ai --abbrev-commit $rev | head -n 1)
		touch -d "$file_time" "$dir/$file"
	done
}
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
if composer --version; then
	# composer hack, see .travis.yml
	sed -i -e 's#pear/#pear-pear.php.net/#' composer.json
	composer install --prefer-dist --no-dev
	# remove bundled deps
	rm -r lib/{Smarty,pear,php-gettext,sphinxapi}
	rm composer.lock
	# cleanup vendors
	rm -r vendor/php-gettext/php-gettext/{tests,examples}
	rm -f vendor/php-gettext/php-gettext/[A-Z]*
	rm -r vendor/smarty-gettext/smarty-gettext/tests
	rm -r vendor/bin
	rm -r vendor/smarty/smarty/{.svn,development,documentation,distribution/demo}
	rm -f vendor/smarty/smarty/distribution/{[A-Z]*,*.{txt,json}}
	rm vendor/composer/*.json

	# this will do clean pear in vendor dir
	touch pear.download pear.install pear.clean
	./update-pear.sh
	rm pear.download pear.install pear.clean
fi

# update to include checksums of js/css files
./dyncontent-chksum.pl

make -C localization install localedir=.
rm -f localization/{tsmarty2c,*.mo}
install -d logs templates_c locks htdocs/customer
touch logs/{cli.log,errors.log,irc_bot.log,login_attempts.log}
chmod -R a+rX .
chmod -R a+rwX templates_c locks logs config
rm -f release.sh update-pear.sh phpxref.cfg phpxref.sh dyncontent-chksum.pl phpcs.xml build.xml pear.xml
rm -f .editorconfig .gitignore .bzrignore composer.json .travis.yml phpunit.xml.dist .php_cs
rm -rf tests

# sanity check
if [ "$rc" != "dev" ]; then
	find -name '*.php' | xargs -l1 php -n -l
fi

rm -rf .bzr*
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
