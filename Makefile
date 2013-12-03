name            := eventum
datadir         := /usr/share/$(name)
sysconfdir      := $(datadir)/config
sbindir         := /usr/sbin
bindir          := /usr/bin
logdir          := /var/log/$(name)
smartyplugindir := $(datadir)/lib/Smarty/plugins

all:
	@echo 'Run "make install" to install eventum.'

install: install-eventum install-cli install-irc install-scm install-libs

dist:
	./release.sh

phpcs:
	phpcs --standard=phpcs.xml --report=emacs --report-width=120 --report-file=`pwd`/phpcs.txt .

# install eventum core
install-eventum:
	install -d $(DESTDIR)$(sysconfdir)
	touch $(DESTDIR)$(sysconfdir)/{config.php,private_key.php,setup.php}

	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/eventum $(DESTDIR)$(datadir)/lib
	cp -a htdocs $(DESTDIR)$(datadir)
	cp -a templates $(DESTDIR)$(datadir)
	cp -a upgrade $(DESTDIR)$(datadir)
	cp -a crons $(DESTDIR)$(datadir)
	cp -a *.php $(DESTDIR)$(datadir)

	install -d $(DESTDIR)$(logdir)
	touch $(DESTDIR)$(logdir)/{cli.log,errors.log,irc_bot.log,login_attempts.log}

# install eventum cli
install-cli:
	install -d $(DESTDIR)$(bindir)
	cp -a cli/$(name).php $(DESTDIR)$(bindir)/$(name)

	install -d $(DESTDIR)$(datadir)/cli
	cp -a cli/lib $(DESTDIR)$(datadir)/cli

# install eventum irc bot
install-irc:
	install -d $(DESTDIR)$(sbindir)
	cp -a irc/eventum-irc-bot.php $(DESTDIR)$(sbindir)/eventum-irc-bot

# install eventum scm (cvs, eventum) hooks
install-scm:
	install -d $(DESTDIR)$(sbindir)
	install -p scm/eventum-cvs-hook.php $(DESTDIR)$(sbindir)/eventum-cvs-hook
	install -p scm/eventum-svn-hook.php $(DESTDIR)$(sbindir)/eventum-svn-hook

# install extra libraries for eventum
install-libs: install-pear install-jpgraph install-gettext install-smarty

install-pear:
	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/pear $(DESTDIR)$(datadir)/lib

install-jpgraph:
	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/jpgraph $(DESTDIR)$(datadir)/lib

install-gettext:
	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/php-gettext $(DESTDIR)$(datadir)/lib

install-smarty:
	install -d $(DESTDIR)$(datadir)/lib
	cp -a lib/Smarty $(DESTDIR)$(datadir)/lib

install-localization:
	$(MAKE) -C localization install
