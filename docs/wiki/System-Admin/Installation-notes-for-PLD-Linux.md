## Requirements

-   [PLD Linux](http://www.pld-linux.org/)
-   Running `mysql` server
-   Domain - a DNS name that you will use for eventum (optional)
-   qmail or postfix for mail routing (optional)

## Main package

Before you continue, please read the generic installation instructions: [Doing a fresh install](Doing-a-fresh-install.md).

```
# poldek -u eventum-setup
```

Follow the on-screen information `eventum` rpm gives you.

After you've configured Eventum via web interface, uninstall the `eventum-setup` package to prevent possible security breach :)

And don't forget to change password for `admin@example.com` if you're going to allow access elsewhere than `localhost` or even better, disable that `admin@example.com` and use your real email.

and if you need then `eventum` configs reside in `/etc/webapps/eventum` including the apache config.

### php.ini settings needed

```
allow_call_time_pass_reference = On
```

## Mail Routing

Mail routing is only done for `qmail` (only because i can't test it elsewhere). Therefore if the `poldek` asks you for `eventum-router` be aware that only `eventum-router-qmail` is functional.

There exists three kinds of data to be routed

-   emails
-   notes
-   drafts

for these exist subpackages:

```
# poldek -u eventum-route-{emails,notes,drafts}
```

and again, follow the on-screen information.

## CLI Interface

CLI allows you access Eventum via your favourite shell

```
# poldek -u eventum-cli
```

before you can use `eventum-cli`, you should setup `~/.eventumrc`

```
$ zcat /usr/share/doc/eventum-cli-*/eventumrc.gz > ~/.eventumrc
$ chmod 600 ~/.eventumrc
$ vi ~/.eventumrc
```

## SCM Integration

`SCM` Integration is currently possible only with `CVS`. For SVN integration have look at [Subversion integration](Subversion-integration.md) page.

```
# poldek -u eventum-scm
```

You should add to your `CVSROOT/loginfo` catchall entry:

```
# process any message with Eventum
ALL  /usr/lib/eventum/scm $USER %{sVv}
```

## IRC Bot

By default IRC Bot notifies to configured channel only new issues. If you need more you should use [Workflow API](Workflow-API.md).

You might want to read [Using the IRC bot](../System-Advanced/Using-the-IRC-bot.md) before configuring your IRC bot.

```
# poldek -u eventum-irc
# vi /etc/eventum/irc.php
# /sbin/service eventum-irc start
```

## Upgrading

Upgrading is handled by rpm `%trigger`-s.

Before you upgrade, make sure that in `/etc/webapps/eventum/config.php` `APP_SQL_DBUSER` has `ALTER` privileges to database. Recent Eventum rpm packages already do so.

If you've done that then database migration should be automatic, if not, further instructions are displayed on screen.

## Uninstalling Eventum

That's simple

```
# poldek -e eventum
```
