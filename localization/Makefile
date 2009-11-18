# Makefile for Eventum po files.
# (c) 2007-2009 Elan Ruusamäe <glen@delfi.ee>

localedir   := /usr/share/locale
POOTLE_URL  := https://www.unixlan.com.ar/eventum
ALL_LINGUAS := de en et es fi fr it nl pl ru sv pt_BR
DOMAIN      := eventum
POFILES     := $(patsubst %,%.po,$(ALL_LINGUAS))

all:
	@set -e; \
	umask 002; \
	for lang in $(ALL_LINGUAS); do \
		echo -n "$$lang: "; \
		[ -f $$lang.po ] || { echo Missing; continue; }; \
		msgfmt --statistics --output=t.mo $$lang.po && mv t.mo $$lang/LC_MESSAGES/$(DOMAIN).mo; \
	done

install: all
	@install -d $(DESTDIR)$(localedir)
	for lang in $(ALL_LINGUAS); do \
		[ -f $$lang/LC_MESSAGES/$(DOMAIN).mo ] || continue; \
		install -d $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES; \
		echo cp -a $$lang/LC_MESSAGES/$(DOMAIN).mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES; \
		cp -a $$lang/LC_MESSAGES/$(DOMAIN).mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES; \
	done

tools-check:
	@TOOLS='bzr find sort xargs xgettext sed mv rm'; \
	for t in $$TOOLS; do \
		p=`which $$t 2>/dev/null`; \
		[ "$$p" -a -x "$$p" ] || { echo "ERROR: Can't find $$t"; exit 1; }; \
	done

# generate .pot file from Eventum svn trunk
pot: tools-check
	@set -x -e; \
	export tsmarty2c=`pwd`/tsmarty2c; \
	umask 002; \
	rm -rf workdir; \
	bzr export workdir; \
	cd workdir; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' -o -name '*.tpl.js' -o -name '*.tpl.xml' | xargs $$tsmarty2c -o ts.pot; \
		find -name '*.php' | xgettext --files-from=- --keyword=gettext --keyword=ev_gettext --output=code.pot; \
		msgcat -o merged.pot code.pot ts.pot; \
		sed -ne '1,/^$$/p' code.pot > header.pot; \
		msgcat -s -o eventum.pot --use-first header.pot merged.pot; \
		mv eventum.pot ..; \
	cd -; \
	rm -rf workdir

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

update-pootle:
	@set -x -e; \
	svn export $(POOTLE_URL) pootle; \
	for lang in $(ALL_LINGUAS); do \
		[ -f pootle/$$lang/eventum.po ] || continue; \
		if msgmerge pootle/$$lang/eventum.po $(DOMAIN).pot -o new.po; then \
			if cmp -s $$lang.po new.po; then \
				rm -f new.po; \
			else \
				mv -f new.po $$lang.po; \
			fi \
		fi \
	done; \
	rm -rf pootle
