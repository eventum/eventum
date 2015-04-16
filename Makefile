name            := eventum
datadir         := /usr/share/$(name)
sysconfdir      := $(datadir)/config
sbindir         := /usr/sbin
bindir          := /usr/bin
logdir          := /var/log/$(name)
smartyplugindir := $(datadir)/lib/Smarty/plugins

php-cs-fixer := $(shell PATH=$$PATH:. which php-cs-fixer.phar 2>/dev/null || which php-cs-fixer 2>/dev/null || echo false)

all:
	@echo 'Run "make install" to install eventum.'

install: install-eventum install-cli install-irc install-scm

dist:
	./bin/release.sh

test:
	phpunit

phpcs:
	phpcs --standard=phpcs.xml --report=emacs --report-width=120 --report-file=`pwd`/phpcs.txt .

box.phar:
	curl -LSs https://box-project.github.io/box2/installer.php | php

composer.phar:
	curl -sS https://getcomposer.org/installer | php

php-cs-fixer.phar:
	curl -sS http://get.sensiolabs.org/php-cs-fixer.phar -o $@.tmp && chmod +x $@.tmp && mv $@.tmp $@

pear-fix: composer.lock
	-$(php-cs-fixer) fix vendor/pear-pear.php.net --fixers=php4_constructor --verbose

phpcs-fix: php-cs-fixer.phar
	-$(php-cs-fixer) fix --verbose

composer.lock:
	composer install

# https://security.sensiolabs.org/api
composer-security-checker: composer.lock
	curl -H "Accept: text/plain" https://security.sensiolabs.org/check_lock -F lock=@composer.lock

# install eventum core
install-eventum:
	install -d $(DESTDIR)$(sysconfdir)
	touch $(DESTDIR)$(sysconfdir)/{config.php,private_key.php,setup.php}

	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/eventum $(DESTDIR)$(datadir)/lib
	cp -a htdocs $(DESTDIR)$(datadir)
	cp -a templates $(DESTDIR)$(datadir)
	cp -a upgrade $(DESTDIR)$(datadir)
	cp -a bin $(DESTDIR)$(datadir)
	cp -a *.php $(DESTDIR)$(datadir)

	install -d $(DESTDIR)$(logdir)
	touch $(DESTDIR)$(logdir)/{cli.log,errors.log,irc_bot.log,login_attempts.log}

# install eventum cli
install-cli:
	install -d $(DESTDIR)$(bindir)
	install -p cli/$(name).phar $(DESTDIR)$(bindir)/$(name)

# install eventum irc bot
install-irc:
	install -d $(DESTDIR)$(sbindir)
	cp -a irc/eventum-irc-bot.php $(DESTDIR)$(sbindir)/eventum-irc-bot

# install eventum scm (cvs, eventum) hooks
install-scm:
	install -d $(DESTDIR)$(sbindir)
	install -p scm/eventum-cvs-hook.php $(DESTDIR)$(sbindir)/eventum-cvs-hook
	install -p scm/eventum-svn-hook.php $(DESTDIR)$(sbindir)/eventum-svn-hook

install-localization:
	$(MAKE) -C localization install
