### Setting up email routing with postfix

## Quick Notes

When you're setting up Eventum's [Email Routing Interface](https://github.com/eventum/eventum/wiki/System-Admin:-Email-Routing-Interface) for postfix, use these options in the `Administration` -> `General Setup` area:

```
Email Routing Interface: Enabled
Recipient Type Flag: [doesn't matter, choose any]
Email Address Prefix: issue-
Address Hostname: [the domain name of the email address issues should be sent to]
Warn Users Whether They Can Send Emails to Issue: [doesn't matter, choose any]

Internal Note Routing Interface: Enabled
Note Address Prefix: note-
Address Hostname: [the domain name of the email address issues should be sent to]

Email Draft Interface: Enabled
Note Address Prefix: draft-
Address Hostname: [the domain name of the email address issues should be sent to]
```

## Postfix configuration

There are different ways to implement the goal.

### using local PHP script

In `/etc/mail/main.cf` define

```
transport_maps = regexp:/etc/mail/transportregex
local_recipient_maps = unix:passwd.byname $alias_maps $transport_maps
```

Be sure to include your domain in mydestination

```
mydestination = $transport_maps, $myhostname, eventum.example.com
```

In `/etc/mail/master.cf` define eventum transport:

```
eventum   unix  -       n       n       -       10       pipe
 flags=DRhu user=apache argv=/usr/bin/php /var/www/html/eventum/misc/route_${nexthop}.php
```

Create `/etc/mail/transportregex` file:

```
/note-.*@eventum.example.com/         eventum:notes
/issue-.*@eventum.example.com/        eventum:emails
/drafts-.*@eventum.example.com/       eventum:drafts
```

Run `postmap` on that file

```
postmap /etc/mail/transportregex
```

Restart your postfix to take into account main.cf and master.cf changes

### forwarding all domain mails to IMAP account

in `/etc/mail/virtual` write

```
@eventum.example.com      eventum_issues@imap.example.com
```

and make define `virtual_maps` to use it:

```
virtual_maps = hash:/etc/mail/virtual
```
