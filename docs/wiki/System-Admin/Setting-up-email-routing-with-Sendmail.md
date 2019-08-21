### Setting up email routing with Sendmail

## Quick Notes

When you're setting up Eventum's [Eventum:Email Routing Interface](Email-Routing-Interface.md) for Sendmail, use these options in the Administration -\> General Setup area:

`Email Routing Interface: Enabled`
`Recipient Type Flag: [doesn't matter, choose any]`
`Email Address Prefix: eventum_issues+`
`Address Hostname: [the domain name of the email address issues should be sent to]`
`Warn Users Whether They Can Send Emails to Issue: [doesn't matter, choose any]`

In your /etc/mail/virtusertable, add the entry:

`eventum_issues@yourdomain.com eventum_issues%3`

(that's tab separated, not space separated, and the %3 on the end is there on purpose)

Then rebuild the virtual email users table with (as root):

`# make -C /etc/mail`

That directs all of the incoming emails for eventum_issues+xyz@yourdomain.com to the email account called "eventum_issues" on the mail server, which you then need to setup Eventum to check occasionally.

### Plussed Users

With modern versions of sendmail (and maybe with not-so modern versions), as long as the prefix used ends with «+» there is no need to put any entries into «`/etc/mail/virtusertable`» at all.

In addition, if you wish to use a prefix that does not directly match the actual mail account (e.g. «`issue+XXX@example.com`» mapped to «`otheraccount@example.com`», this can be done with an entry in «`/etc/mail/aliases`» (or «`/etc/aliases`») like this:

`issue+*: otheraccount`
