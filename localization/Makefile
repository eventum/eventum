# Makefile for Eventum po files.
# (c) 2007-2010 Elan Ruusamäe <glen@delfi.ee>

localedir   := /usr/share/locale
DOMAIN      := eventum
MOFILES     := $(patsubst %.po,%.mo,$(wildcard *.po))

all: $(MOFILES)

# create mo from the po files
%.mo: %.po
	msgfmt --statistics $< -o $(subst .po,,$<).mo.tmp && mv $(subst .po,,$<).mo.tmp $(subst .po,,$<).mo

install: $(MOFILES)
	install -d $(DESTDIR)$(localedir)
	@for mo in $(MOFILES); do \
		lang=$$(basename $$mo .mo); \
		install -d $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES; \
		echo cp -a $$mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo; \
		cp -a $$mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo; \
	done

clean:
	rm -f *.mo

tools-check:
	@TOOLS='bzr find sort xargs xgettext sed mv rm'; \
	for t in $$TOOLS; do \
		p=`which $$t 2>/dev/null`; \
		[ "$$p" -a -x "$$p" ] || { echo "ERROR: Can't find $$t"; exit 1; }; \
	done

# generate .pot file from clean copy
pot: tools-check
	@set -x -e; \
	export tsmarty2c=`pwd`/tsmarty2c; \
	umask 002; \
	rm -rf workdir; \
	bzr export workdir; \
	cd workdir; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' -o -name '*.tpl.js' -o -name '*.tpl.xml' | xargs $$tsmarty2c -o ts.pot; \
		find -name '*.php' | xgettext --files-from=- --add-comments=TRANSLATORS: --keyword=gettext --keyword=ev_gettext --output=code.pot; \
		msgcat -o merged.pot code.pot ts.pot; \
		sed -ne '1,/^$$/p' code.pot > header.pot; \
		msgcat -s -o eventum.pot --use-first header.pot merged.pot; \
		mv eventum.pot ..; \
	cd -; \
	rm -rf workdir

# update pot -> po all languages
update-po:
	@set -e; \
	umask 002; \
	for po in $(wildcard *.po); do \
		echo -n $$po; \
		msgmerge -U -w 78 $$po $(DOMAIN).pot; \
	done
