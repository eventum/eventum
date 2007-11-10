# Makefile for Eventum po files.
# (c) 2007 Elan Ruusam√e <glen@delfi.ee>

SVN_URL := svn://eventum.mysql.org/eventum-gpl/trunk/eventum
ALL_LINGUAS := de en es fi fr it nl pl ru sv
DOMAIN := eventum
POFILES := $(shell echo $(ALL_LINGUAS) | sed -e 's, ,.po ,g;s,$$,.po,')

all:
	@set -x -e; \
	for lang in $(ALL_LINGUAS); do \
		[ -f $$lang.po ] || continue; \
		msgfmt --statistics --output=t.mo $$lang.po && mv t.mo $$lang/LC_MESSAGES/$(DOMAIN).mo; \
	done

# generate .pot file from Eventum svn trunk
pot:
	@set -x -e; \
	rm -rf export; \
	svn export $(SVN_URL) export; \
	cd export; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' | LC_ALL=C sort | xargs tsmarty2c > misc/localization/eventum.c; \
		(echo misc/localization/eventum.c; find -name '*.php' | LC_ALL=C sort) | xgettext --files-from=- --keyword=gettext --keyword=ev_gettext --output=misc/localization/eventum.pot; \
		mv misc/localization/eventum.{c,pot} ..; \
	cd -; \
	rm -rf export

update-po:
	@set -x -e; \
	for lang in $(ALL_LINGUAS); do \
		[ -f $$lang.po ] || continue; \
		if msgmerge $$lang.po $(DOMAIN).pot -o new.po; then \
			if cmp -s $$lang.po new.po; then \
				rm -f new.po; \
			else \
				mv -f new.po $$lang/LC_MESSAGES/$(DOMAIN).po; \
			fi \
		fi \
	done
