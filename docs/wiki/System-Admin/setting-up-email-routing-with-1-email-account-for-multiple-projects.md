### Setting up email routing with 1 email account for multiple projects

## Note

This is an application of the [Multiple project email workaround](Multiple project email workaround "wikilink") using the [Email Routing Interface](Email-Routing-Interface.md). The multiple project email workaround **_does not require_** the use of the Email Routing Interface, and can be applied to the other methods of associating incoming email with issues described in [Email integration](Email-integration.md)

## Steps

These steps should allow you to setup the [Email Routing Interface](Email-Routing-Interface.md) (ERI) for all of your eventum projects using a single email account.
Using Eventum 1.5.4 to create these steps.

1. Create an email account on your mail server (I chose eventum_issues@example.com).
2. Enable 'Address Extensions' for your mail server. For example, this would allow an email addressed to eventum_issues+28@example.com to be delivered to the email account eventum_isses@example.com. Eventum ERI uses the address extenion to know which issue to associate the email.
   In postfix, this is in the main.cf file, the setting is 'recipient_delimiter = +', the default for postfix is a '+'
3. Test your account to make sure it works with address extensions. I use postfix virtual domains with maildrop, so I also needed to edit master.cf to get maildrop to accept address extensions. Otherwise maildrop will return the email as 'unknown user'.

```text
 maildrop unix - n n - - pipe
 flags=Ru user=vmail argv=/usr/bin/maildrop -d \${user}@\${nexthop} \${extension} \${recipient} \${user} \${nexthop}
```

4. Under the 'general setup' page in Eventum, click the radio button to enable the 'Email Routing Interface'.
5. In the address prefix enter 'eventum_issues+' (the email account you setup in step 1 plus the address extension symbol).
6. Enter the host name 'example.com'
7. Click the radio button to enable 'Email Integration Feature'.
8. Save settings for the 'general setup' page
9. Click on 'Manage Email Accounts'
10. Create a working email account entry [eventum_issues@example.com] in Manage Email Accounts for the account you created in step 1. Make sure to test it out. You can associate the email account with any of the projects. Pick one.
11. Create dummy email accounts for your remaining projects. These are necessary (currently) because of an old design that will likely change in a future release.
12. Add a crontab entry for /eventum/misc/download_emails.php with the appropriate arguments as documented in the INSTALL file. Below is a sample crontab for an IMAP account

```text
0 * * * * cd /var/www/eventum/misc; /usr/local/bin/php -q download_emails.php eventum_issues@example.com mail.example.com INBOX
```
