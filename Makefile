name            := eventum
datadir         := /usr/share/$(name)
sysconfdir      := $(datadir)/config
sbindir         := /usr/sbin
bindir          := /usr/bin
logdir          := /var/log/$(name)
smartyplugindir := $(datadir)/lib/Smarty/plugins

PHPCOMPATINFO_VERSION := 5.0.1
PHPUNIT_VERSION := 4.8.11
PHPAB_VERSION := 1.20.3

define find_tool
$(shell PATH=$$PATH:. which $1.phar 2>/dev/null || which $1 2>/dev/null || echo false)
endef

define fetch_tool
curl -sSf $1 -o $@.tmp && chmod +x $@.tmp && mv $@.tmp $@
endef

php-cs-fixer := $(call find_tool, php-cs-fixer)
phpcompatinfo := $(call find_tool, phpcompatinfo)
gush := $(call find_tool, gush)

all:
	@echo 'Run "make install" to install eventum.'

pot:
	$(MAKE) -C localization pot
	if test -d ../po; then \
		test -d ../po/.bzr && (cd ../po && bzr pull); \
		cp localization/*.pot ../po/localization; \
		test -d ../po/.bzr && (cd ../po && bzr commit -m "update .pot" && bzr push); \
	fi

install: install-eventum install-cli

dist:
	./bin/release.sh

quickdist:
	QUICK=true ./bin/release.sh

test:
	phpunit

box.phar:
	curl -LSs https://box-project.github.io/box2/installer.php | php

composer.phar:
	curl -sS https://getcomposer.org/installer | php

php-cs-fixer.phar:
	$(call fetch_tool,http://get.sensiolabs.org/php-cs-fixer.phar)

phpcompatinfo.phar:
	$(call fetch_tool,http://bartlett.laurent-laville.org/get/phpcompatinfo-$(PHPCOMPATINFO_VERSION).phar)

phpunit.phar:
	$(call fetch_tool,https://phar.phpunit.de/phpunit-$(PHPUNIT_VERSION).phar)

phpab.phar:
	$(call fetch_tool,http://phpab.net/phpab-$(PHPAB_VERSION).phar)

gush.phar:
	$(call fetch_tool,http://gushphp.org/gush.phar)

codecept.phar:
	$(call fetch_tool,http://codeception.com/codecept.phar)

pear-fix: composer.lock
	-$(php-cs-fixer) fix vendor/pear-pear.php.net --fixers=php4_constructor --verbose

phpcs-fix: php-cs-fixer.phar
	-$(php-cs-fixer) fix --verbose

phpcompatinfo: phpcompatinfo.phar
	$(phpcompatinfo) analyser:run --alias current

changelog:
	$(gush) branch:changelog

composer.lock:
	composer install

# https://security.sensiolabs.org/api
composer-security-checker: composer.lock
	curl -H "Accept: text/plain" https://security.sensiolabs.org/check_lock -F lock=@composer.lock

# install eventum core
install-eventum:
	install -d $(DESTDIR)$(sysconfdir)
	cp -a config/* $(DESTDIR)$(sysconfdir)

	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/eventum $(DESTDIR)$(datadir)/lib
	cp -a htdocs $(DESTDIR)$(datadir)
	cp -a templates $(DESTDIR)$(datadir)
	cp -a upgrade $(DESTDIR)$(datadir)
	cp -a bin $(DESTDIR)$(datadir)
	cp -a src $(DESTDIR)$(datadir)
	cp -a res $(DESTDIR)$(datadir)
	cp -a *.php $(DESTDIR)$(datadir)

	install -d $(DESTDIR)$(logdir)
	cp -a var/log/* $(DESTDIR)$(logdir)

# install eventum cli
install-cli:
	install -d $(DESTDIR)$(bindir)
	install -p cli/$(name).phar $(DESTDIR)$(bindir)/$(name)

install-localization:
	$(MAKE) -C localization install
