# Makefile for Eventum po files.
# (c) 2007 Elan Ruusam√e <glen@delfi.ee>

SVN_URL := svn://eventum.mysql.org/eventum-gpl/trunk/eventum
ALL_LINGUAS := de en es fi fr it nl pl ru sv
DOMAIN := eventum
POFILES := $(patsubst %,%.po,$(ALL_LINGUAS))

all:
	@set -x -e; \
	umask 002; \
	for lang in $(ALL_LINGUAS); do \
		[ -f $$lang.po ] || continue; \
		msgfmt --statistics --output=t.mo $$lang.po && mv t.mo $$lang/LC_MESSAGES/$(DOMAIN).mo; \
	done

# generate .pot file from Eventum svn trunk
pot:
	@set -x -e; \
	umask 002; \
	rm -rf export; \
	svn export $(SVN_URL) export; \
	cd export; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' | LC_ALL=C sort | xargs tsmarty2c > misc/localization/eventum.c; \
		(echo misc/localization/eventum.c; find -name '*.php' | LC_ALL=C sort) | xgettext --files-from=- --keyword=gettext --keyword=ev_gettext --output=misc/localization/eventum.pot; \
		sed -i -e 's,misc/localization/eventum.c:[0-9]\+,misc/localization/eventum.c,g' misc/localization/eventum.pot; \
		mv misc/localization/eventum.{c,pot} ..; \
	cd -; \
	rm -rf export

update-po:
	@set -x -e; \
	umask 002; \
	for lang in $(ALL_LINGUAS); do \
		[ -f $$lang.po ] || continue; \
		if msgmerge $$lang.po $(DOMAIN).pot -o new.po; then \
			if cmp -s $$lang.po new.po; then \
				rm -f new.po; \
			else \
				mv -f new.po $$lang.po; \
			fi \
		fi \
	done
