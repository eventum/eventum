# Makefile for Eventum
# (c) 2007 Elan Ruusam√e <glen@delfi.ee>

all:

dist:

# generate .pot file from Eventum svn trunk
pot:
	@set -x -e; \
	rm -rf export; \
	svn export svn://eventum.mysql.org/eventum-gpl/trunk/eventum export; \
	cd export; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' | LC_ALL=C sort | xargs tsmarty2c > misc/localization/eventum.c; \
		(echo misc/localization/eventum.c; find -name '*.php' | LC_ALL=C sort) | xgettext --files-from=- --keyword=gettext --keyword=ev_gettext --output=misc/localization/eventum.pot; \
		mv misc/localization/eventum.{c,pot} ..; \
	cd -; \
	rm -rf export

update-po:
