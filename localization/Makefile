# Makefile for Eventum po files.
# (c) 2007-2016 Elan Ruusamäe <glen@delfi.ee>

localedir   := .
DOMAIN      := eventum
MOFILES     := $(patsubst %.po,%.mo,$(wildcard *.po))
tsmarty2c   := $(abspath ..)/vendor/bin/tsmarty2c.php

all: $(MOFILES)

# create mo from the po files
%.mo: %.po
	msgfmt --statistics $< -o $(subst .po,,$<).mo.tmp && mv $(subst .po,,$<).mo.tmp $(subst .po,,$<).mo

LINGUAS.php: $(wildcard *.po) Makefile
	exec 1> $@; \
	echo '<?php'; \
	echo '$$avail_langs = array('; \
	for po in $(wildcard *.po); do \
		code=$$(basename $$po .po); \
		lang=$$(sed -ne 's/"Language-Team: \(.*\)/\1/p' $$po | sed -e 's, <.*,,;s,\\n.*,,'); \
		echo "\t// TRANSLATORS: Translation of $$lang language in preferences dropdown"; \
		echo "\t'$$code' => _('$$lang'),"; \
	done; \
	echo ');'
	php -n -l $@

install: $(MOFILES)
	install -d $(DESTDIR)$(localedir)
	@for mo in $(MOFILES); do \
		lang=$$(basename $$mo .mo); \
		install -d $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES; \
		echo cp -p $$mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo; \
		cp -p $$mo $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo; \
	done

clean:
	rm -f *.mo

$(tsmarty2c):
	@set -e; \
	echo "\nMissing tsmarty2c from gettext-smarty\n"; \
	echo "Run 'composer update' in '$(abspath ..)'\n"; \
	exit 1

tools-check: $(tsmarty2c)
	@TOOLS='git find sort xargs xgettext sed mv rm'; \
	which --version > /dev/null 2>&1 || which() {\
		local ifs=$$IFS d x=$$1; IFS=:; \
		for d in $$PATH; do [ -x $$d/$$x ] && { p=$$d/$$x; break; }; done; \
		IFS=$$ifs; \
		echo $$p; \
	}; \
	for t in $$TOOLS; do \
		p=`which $$t 2>/dev/null`; \
		[ "$$p" -a -x "$$p" ] || { echo "ERROR: Can't find $$t"; exit 1; }; \
	done

# setup workdir from git index
SETUP_WORKDIR=git archive HEAD

# build pot from current workdir, not from git index
local-pot:
	$(MAKE) pot SETUP_WORKDIR="tar -cf - . --exclude=vendor --exclude=po --exclude=workdir"

# generate .pot file from clean copy
pot: tools-check
	# note about History::add: it's little hack, as can't specify :: in --keyword arg
	set -x -e; \
	umask 002; \
	rm -rf workdir; \
	install -d workdir; \
	(cd .. && $(SETUP_WORKDIR)) | tar -x -C workdir; \
	cd workdir; \
		find templates -name '*.tpl.html' -o -name '*.tpl.text' -o -name '*.tpl.js' -o -name '*.tpl.xml' | xargs $(tsmarty2c) -o ts.pot; \
		grep -rl History::add src lib htdocs | xargs sed -i -e 's/History::add/History__add/g'; \
		find -name '*.php' | xgettext -L PHP --files-from=- --add-comments=TRANSLATORS: \
			--keyword=gettext --keyword=ev_gettext \
			--keyword=History__add:4 \
			--output=code.pot; \
		msgcat -o merged.pot code.pot ts.pot; \
		sed -ne '1,/^$$/p' code.pot > header.pot; \
		msgcat -s -o $(DOMAIN).pot --use-first header.pot merged.pot; \
		\
		msgcat ../$(DOMAIN).pot --no-location --sort-output | sed -ne '/^$$/,$$p' > old; \
		msgcat $(DOMAIN).pot --no-location --sort-output | sed -ne '/^$$/,$$p' > new; \
		if diff -u old new; then \
			echo No changes; \
		else \
			mv $(DOMAIN).pot ..; \
		fi; \
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

# update .po file timestamp from PO-Revision-Date header
touch-po:
	@set -e; \
	for po in $(wildcard *.po); do \
		d=`awk '/PO-Revision-Date:/ { sub(/\\\\n"/, ""); print $$2 " " $$3 } ' $$po`; \
		test -n "$$d" || continue; \
		echo touch -d "$$d" $$po; \
		touch -d "$$d" $$po; \
	done
