# Eventum Issue Tracking System

## [3.2.2] - 2017-??-??

Upgrading to 3.2.x versions requires that you upgrade to 3.2.0 version first.

- cleanup templates for `core.current_url` (@glensc, #265)
- fix undefined keys in `assigned.tpl.text` (@glensc)
- fix bug with unassigned restricted issues not showing on list issues page (@balsdorf)
- stop emails that open an issue from prematurely marking an issue as updated (@balsdorf)
- fix infinite redirect loop on anonymous post page (@balsdorf)
- add history entry when issue is moved between projects (@balsdorf)
- generate proper message-id in `Issue::close()` (@glensc, 8a046ea)
- add example slack error logger (@glensc, e7600031f)
- Fix bug auto switching project on update issues page (@balsdorf, 6ffecfc)
- Catch exception if invalid message is added to mail queue (@balsdorf, 0e55ae2)
- add real 'Status Change Date' column and rename old column to 'Status Action Date' (@balsdorf, #277)

[3.2.2]: https://github.com/eventum/eventum/compare/v3.2.1...master

## [3.2.1] - 2017-06-09

Upgrading to 3.2.x versions requires that you upgrade to 3.2.0 version first.

- add `$min_role` to base controller (@glensc, #261)
- require `iconv` and `mbstring` extensions (@glensc, #269)
- add Event/EventDispatcher to Eventum (@glensc, #272)
- drop old db migrate system (@glensc, #270)
- escape time tracking summary when editing (@balsdorf, #273)
- include zend-mail transport and protocol classes (@glensc, #274)
- improved confusing reminder admin by redirecting after submit (@balsdorf, ff26fcd8)

[3.2.1]: https://github.com/eventum/eventum/compare/v3.2.0...v3.2.1

## [3.2.0] - 2017-05-20

This is pre-release, use with care!

This version switches to use [PDO MySQL] driver, ensure you have the extension enabled.
Even it will likely work, it's recommended that you update to latest 3.1 first before upgrading to 3.2.0.
Consult [Upgrade] wiki page how to upgrade from previous versions.

This version adds replacement for backend classes with adding Extension support, see #259.

- replace buggy `Mail_Helper::getEmailAddresses` with Zend\Mail based implementation (@glensc, #238)
- enable filtering by Severity (@balsdorf, f387fd6)
- drop PEAR DB support, only PDO is supported (@glensc, #252)
- use Zend\Mail\Transport for Mail delivery (@glensc, #237)
- use phinx for database migrations (@glensc, #235)
- notify notification list when changing assignment (@balsdorf, 1527b68)
- unify backend class loading (@glensc, #257)
- use font awesome (@glensc, #253)
- use ctrl/cmd enter to submit forms (@glensc, #255)
- quote custom field names (@glensc, #258)
- drop `Mail_rfc822` PEAR Mail requirement (@glensc, #256)
- add userfile.js, userscript.css support (@glensc, #264)
- fix expected resolution date being rendered as -1 (@glensc, #260)
- fix user roles being overridden when updating a project (@balsdorf, 7ae6d2563, #152)
- add extension support for workflows/customfields/partners/crm (@glensc, #259)
- with this release, reached 🕒 8000th commit!

[3.2.0]: https://github.com/eventum/eventum/compare/v3.1.10...v3.2.0
[PDO MySQL]: http://php.net/manual/en/ref.pdo-mysql.php
[Upgrade]: https://github.com/eventum/eventum/wiki/Upgrading

## [3.1.10] - 2017-04-21

- ldap: respect `create_users` configuration (@glensc)
- Fix bug creating new priority (@balsdorf)
- add MailTransport class to encapsulate smtp transport (@glensc, #236, #234)
- Add option to control ordering of custom field options (@balsdorf, #240)
- Check for release name uniqueness (@balsdorf, #246)
- Add a per project recipient flag for outgoing emails (@balsdorf, #247)

[3.1.10]: https://github.com/eventum/eventum/compare/v3.1.9...v3.1.10

## [3.1.9] - 2017-01-10

- Escape url parameter on login screen (florian.platzer@gmx.de, steffen-sanwald@gmx.net)

[3.1.9]: https://github.com/eventum/eventum/compare/v3.1.8...v3.1.9

## [3.1.8] - 2016-12-30

- fix smarty autoloader (@glensc, 0789211c)

[3.1.8]: https://github.com/eventum/eventum/compare/v3.1.7...v3.1.8

## [3.1.7] - 2016-12-28

- Add workflow method for moving an issue between projects (@balsdorf, #223)
- Replace XMLRPC PEAR implementation with phpxmlrpc (@glensc, #228)
- Don't QP encode sender when storing in the DB(@balsdorf, #226, #225)
- Don't QP encode sender when storing in the DB (@balsdorf, #226, #225)
- Replace XMLRPC PEAR implementation with phpxmlrpc (@glensc, #228)
- Add icons for priority (@phavel, #224)
- Allow sort by "% Complete" column (@phavel, #229, #188)
- Fix some strict mode issues (@glensc, #218, #230)

[3.1.7]: https://github.com/eventum/eventum/compare/v3.1.6...v3.1.7

## [3.1.6] - 2016-12-08

- Fix error caused by incomplete legacy code removal (@balsdorf, #221)
- Swap order of note reply buttons for consistency (@balsdorf)
- Add add some missing translate of strings (@phavel, #222)

[3.1.6]: https://github.com/eventum/eventum/compare/v3.1.5...v3.1.6

## [3.1.5] - 2016-11-23

Please make sure your database is backed up before applying this upgrade. We are
removing some legacy tables and fields which should not contain any data but
backups should be made as a precaution. Please see the following issue for more
details: #219

- Fix SCM checkins being displayed as "public" (@glensc, #215, #216)
- Make public attachments visible for Viewers (@glensc, #214, #217)
- Removed outdated / unused Impact Analysis code (@balsdorf, #219)
- Re-apply patches number 28 because they could have not been executed (@glensc, #220)

[3.1.5]: https://github.com/eventum/eventum/compare/v3.1.4...v3.1.5

## [3.1.4] - 2016-10-26

- Add 'reply as email' option to notes (@balsdorf, #205)
- Encryption: assume no key present if `secret_key.php` file is empty (@glensc)
- Fix "Available Languages" preferences save bug (@glensc, @yangmx, #195, #194)
- Fix bugs in issue association code (@glensc, #207)
- Fix `PEAR_Error` class autoload error (@glensc, #200)
- Reset `sql_mode` also for PDO driver (@glensc, #176)
- Throw Exception in CLI if Eventum is not configured (@glensc, 9f04950)
- Fix Time Tracking administration bugs (@glensc, @yangmx, #197, #196, #208)
- Add back Authorized Repliers user picker (@glensc, #210)
- Removed non '$core' default variables from templates (@balsdorf, #211)
- Allow replies to original message to use "in-reply-to" header for message-id matching (@cpinfold, #212)
- Remove dangerous feature: removing projects and issues by project (@glensc, @balsdorf, #206)
- Fix bug with SphinxSearch not showing excerpts (Bryan)
- Fix bug with SphinxSearch including removed notes (Bryan)
- Fix bug with SphinxSearch not returning all results (Bryan)
- Fix bug where issue closed notifications went to all users even when "internal" was selected (Bryan)
- Rewrite locking code to use flock (@glensc, #209)

[3.1.4]: https://github.com/eventum/eventum/compare/v3.1.3...v3.1.4

## [3.1.3] - 2016-09-25

- Allow time tracking entries to be edited (@balsdorf, #174)
- Update weekly report to look at time tracking when calculating touched issues. (@balsdorf, #175)
- Allow SymphonySession to co-exist with native session handling code (@balsdorf)
- Fix 'get email' command in CLI (@balsdorf)
- Fix bug with page specific JS not being called (@balsdorf)
- Fix bug where session variable was never returned (@balsdorf)
- Add project selection to CLI getWeeklyReport (@balsdorf)
- Add category to notification emails (@balsdorf)
- Don't display Status Change Date column if it has not been customized (@balsdorf)
- Strip tabs and newlines from note / email subjects (@balsdorf)
- Do not associate forwarded emails to original issue (@glensc)
- Add travis configuration to make releases (@glensc, #198)
- Keep `iss_original_description` in getIssueDetails method (@glensc, 98916a8)

[3.1.3]: https://github.com/eventum/eventum/compare/v3.1.2...v3.1.3

## [3.1.2] - 2016-06-06

Previous version enabled PDO driver for all installations, but PDO was supposed
to be used for new installations, also PDO driver has issues for non-UTF8
`APP_CHARSET` setups, this was fixed in (de5e869) so that PDO is used only for
new installations to ensure safer upgrades.

This version fixes login for Eventum installs using more than one project (3281d6d)

- set alternative page class for list issues page. (@balsdorf, #171)
- use Pdo for new installations, keep Pear for older ones (@glensc, de5e869, a920484, #167)
- fix login page css. (@glensc, 2d2923c, #170)
- drop unneeded session init (@glensc, 3281d6d, #168)

[3.1.2]: https://github.com/eventum/eventum/compare/v3.1.1...v3.1.2

## [3.1.1] - 2016-05-29

This version switches to PDO driver by for new installations (#167)
Additionally tables will be renamed without `eventum_` table prefix (#166)

- remove support for calling deprecated handleAssignment workflow method, it was deprecated in 2.4.0-pre1 (d16ea3a)
- drop php 5.3 `$this` hacks (c58fe0c)
- drop `dispelMagicQuotes`; magic quotes support dropped in php 5.4 (e58926c)
- use short array syntax (92c751e)
- add Message-Id column to mail queue table (@glensc, #140)
- cleanup deprecated `APP_LOCAL_PATH` from the template search path (8cea94b)
- drop unused Misc::getInput (5b97d89)
- drop prompt and getInputLine from Misc (83efe3c)
- drop unused Misc::collect (51eab96)
- drop table prefix support (@glensc, #166)
- drop old `APP_SQL_` constants support, support upgrade from earlier than 3.0 versions dropped (30130ba)
- do not allow a manager user to edit an administrative user (@balsdorf, 83ace86, cf93b17)
- use `pdo_mysql` as default mysql driver (@glensc, #167)
- use Symfony Session for session usage (@glensc, #168)
- use Symfony FlashBag for flash messages (@glensc, #169)
- automatically set page ID based on template path and name (@balsdorf, #170)

[3.1.1]: https://github.com/eventum/eventum/compare/v3.1.0...v3.1.1

## [3.1.0] - 2016-04-28

The minimum supported PHP version from this version onwards is 5.5 (5.6 recommended).
While this release still works with 5.3, it is not supported anymore.
This release also no longer bundles SCM hook scripts, they are available from separate project.

- dropped scm git submodule and from install system
- dropped deprecated `bin/route_*.php` scripts, use `process_all_emails.php`
- add workflow methods for crypt upgrade/downgrade (@glensc, #165)
- we reached [7000th] commit! :boom:
- scm: modularize and add gitlab adapter (@glensc, #159)

[3.1.0]: https://github.com/eventum/eventum/compare/v3.0.12...v3.1.0
[7000th]: https://gitter.im/eventum/eventum?at=571fcd410f156f102b41020c

## [3.0.12] - 2016-04-19

This will be last release supporting PHP 5.3, next version will require PHP 5.5 and be versioned as 3.1.0.
This will also be last release packaging SCM hook scripts in main Eventum release tarball.

- Make Bulk update feature work again (@balsdorf, #160, #161)
- Nice Progress Bar for % Complete (@phavel, @glensc, @slay123, #162)
- Fix bug where percentage complete is not included in changed notification (@balsdorf, #163, #164)

[3.0.12]: https://github.com/eventum/eventum/compare/v3.0.11...v3.0.12

## [3.0.11] - 2016-03-28

- Updated Misc::activateLinks to not activate mail links inside of urls (@balsorf, d23e712)
- Allow separate role for editing custom fields vs viewing (@balsdorf, #149)
- Configuring loggers via config file using Monolog-Cascade (@glensc, #146)
- Use Zend\Mail in MailQueue::addMail (@glensc, #139)
- Setup correct project roles when updating user projects (@Alexey-Architect, #152)
- Remove "Product Version" field from view issue page since it is bundled with "Product" (@balsdorf)

[3.0.11]: https://github.com/eventum/eventum/compare/v3.0.10...v3.0.11

## [3.0.10] - 2016-02-29

- Add back notification user picker (@glensc, #34)
- Autosave notes/emails/etc to local storage in case of browser crash (@balsdorf, @glensc, @slay123, #145)
- Allow issue view access to be restricted to assignees or groups (@balsdorf #141, #148)
- Collapse replies in email like GMail, GitHub do (@glensc, #143)

[3.0.10]: https://github.com/eventum/eventum/compare/v3.0.9...v3.0.10

## [3.0.9] - 2016-02-06

This release highlights optional support to encrypt DB, IMAP/POP3, LDAP passwords (#134)
and allowing users to be in multiple groups (#135).

- Deprecate `bin/route_*.php` scripts in favour of `bin/process_all_emails.php` (@glensc, a4ea0c5)
- Add support to (re)-run specific patch by it's number (@glensc, 16cb41d)
- Fix wrapping the long lines (@slay123, #133)
- Fixes to allow CLI to be built without errors (@balsdorf, #130)
- Reuse existing Routing::route to route mails from support::getEmailInfo (@glensc, #131)
- Allow users to be in multiple groups (@balsdorf, #135)
- Convert report pages to Controller logic (@glensc, #129)
- Move bin script to use Command class (@glensc, #137)
- Move Db classes under Eventum\Db namespace (@glensc, #136)
- Add $scm parameter to handleSCMCheckins workflow method (@glensc)
- Improvements to manage/users page, add datatables paginator (@glensc)
- Optional support to encrypt (DB, IMAP/POP3, LDAP) passwords (@glensc, #134)

[3.0.9]: https://github.com/eventum/eventum/compare/v3.0.8...v3.0.9

## [3.0.8] - 2016-01-18

From release version 3.0.4 a bug existed where logged in users could
incorrectly access some management pages (60866f8d). Please upgrade to 3.0.8
immediately.

- Add "Reply as Note" to emails (@balsdorf)
- Fix Reply subjects when sending notes (@glensc)
- Add preference support to turn off relative dates (@balsdorf, #125)
- Upload on paste from clipboard (@glensc, #126)
- Fix multiple chosen selections overlapping next line (@slay123, aa5e352)
- Improve user manage page (@glensc, 5ff030b)
- Convert manage pages to Controller logic (@glensc, #128)
- Add severity descriptions to issue update page (@balsdorf, #37)

[3.0.8]: https://github.com/eventum/eventum/compare/v3.0.7...v3.0.8

## [3.0.7] - 2015-12-31

Release highlights are new monolog based logging (#97), showing dates human
friendly (#116) and introduction of API tokens (besides passwords) for remote
access (#122)

- emails.php: handle better empty "From:" header (@glensc, #91)
- Added ability to require custom fields on the edit form (@balsdorf, #107)
- Add logging framework based on monolog (@glensc, #97)
- Error in the first pie chart in main.php (@glensc, #103)
- Scheduled Release field loses selected value when updating issue (@balsdorf, #105)
- Fix static notifications in class.issue.php (@cpinfold, #101)
- Add .htaccess to project root (@glensc, #104)
- Add Controller to pages (@glensc, #108, #117, #120)
- Allow auth backends to auto redirect to external login screen (@balsdorf, #109)
- Improve select project page and increase project cookie lifespan (@balsdorf, #110)
- 3.0.6: General Setup: SMTP: Requires Authentication: Radio Buttons (@glensc, #112)
- config.php: APP_xxx_COLOR: CSS (@glensc, #114)
- setup header comment for all files (@glensc, @balsdorf, #115)
- show dates human friendly (@glensc, #116)
- add .htaccess to htdocs (@glensc, #118)
- Add API Tokens support for authentication for RPC/CLI (@balsdorf, @glensc, #122)
- Added option to add users to authorized repliers list when sending email (@balsdorf, #123)
- Fix default Notification options (@glensc, #121)
- Exclude sender of email from getting standard "new issue" email (@balsdorf, #113, #124)
- Restore "remember me" in template, lost in 2.4.0 release (@glensc, aec62f5)
- Restore usability of "Add Unknown Recipients to Issue Notification List" checkbox (@balsdorf)

[3.0.7]: https://github.com/eventum/eventum/compare/v3.0.6...v3.0.7

## [3.0.6] - 2015-11-10

This release highlight is automatic password hashes upgrade to be more secure
on user authentication (sign in). You can force all users to re-authenticate by
regenerating Eventum private key from Administration panel. (See #93).

- Update custom fields from update issue page (Bryan Alsdorf, #88)
- Allow time category/summary to be set when sending emails (Bryan Alsdorf)
- Add missing 'Scheduled Release' and 'Group' field back to update page (Bryan Alsdorf, #89)
- Tiny change to submit on project selection (Craig Pinfold, #92)
- Fix error updating 'Completion Percentage' #94
- Upgrade password hash on successful login (Elan Ruusamäe, #93)
- Use AJAX for /manage/email_accounts.php test (Craig Pinfold, #96)
- LDAP auth backend: create connection only if needed (Elan Ruusamäe)

[3.0.6]: https://github.com/eventum/eventum/compare/v3.0.5...v3.0.6

## [3.0.5] - 2015-11-02

- Fix routing settings read error (Elan Ruusamäe, #80)

[3.0.5]: https://github.com/eventum/eventum/compare/v3.0.4...v3.0.5

## [3.0.4] - 2015-10-31

To simplify setup and directory layout we have moved all directories that
contain files to which Eventum writes data during the course of its operation
into `var/`. You need to grant write permissions on `/path/to/eventum/var/`
sub-directories to your webserver. (#81)

New passwords are saved using more secure hashing than before (#77)

This release was buggy and was yanked, bug itself is fixed in v3.0.5

- Fix few Static & Deprecated calls (Craig Pinfold, #72)
- Use randomlib for private key generation, add UI to regenerate it (Elan Ruusamäe, #73)
- Fix misplaced {if} in preferences template (Robbert-Jan Roos, [LP#1506279])
- Auth and Project cookie related internal refactor (Elan Ruusamäe, #74)
- Set limit 20 retries to try to send one mail (Elan Ruusamäe)
- Add html_charset to be APP_CHARSET (Elan Ruusamäe, [LP#741768])
- Use password_hash family functions for password hashing (Elan Ruusamäe, GH#77)
- Unify size of pri_id in databases (Elan Ruusamäe, [LP#1450152])
- Handle mbstring function overload (Elan Ruusamäe, [LP#1494732])
- Make version table log based (Elan Ruusamäe)
- Removed local/include/ from include path and added to composer instead (Bryan Alsdorf)
- Use zf2 config for setup config (Elan Ruusamäe, #80)
- Improvements to messageId generator, make it use RandomLib (Elan Ruusamäe)
- IRC bot improvements (Elan Ruusamäe, #82)
- Use var/ path for writable data (Elan Ruusamäe, #81)
- Pass array of changed fields to Workflow::handleCustomFieldsUpdated (Bryan Alsdorf)
- Custom Fields Weekly report: take also params from GET (Kristo Klausson, #86)
- Manage Emails: Accept prj_id from GET to allow link bookmarking (Elan Ruusamäe)
- Add DebugBar debug bar in development mode (Elan Ruusamäe, #87)

[3.0.4]: https://github.com/eventum/eventum/compare/v3.0.3...v3.0.4
[LP#741768]: https://bugs.launchpad.net/eventum/+bug/741768
[LP#1450152]: https://bugs.launchpad.net/eventum/+bug/1450152
[LP#1494732]: https://bugs.launchpad.net/eventum/+bug/1494732
[LP#1506279]: https://bugs.launchpad.net/eventum/+bug/1506279

## [3.0.3] - 2015-10-13

This release includes copy of wiki documents in release tarball.

- Added bin/truncate_mail_queue.php (Bryan Alsdorf)
- Add admin interface for required fields (Bryan Alsdorf, #67)
- UI fix for Issue Assignees (Kristo Klausson, #68)
- Remove File/Util.php manual include (Elan Ruusamäe, [LP#1494536])
- Eventum Mail Processing Enhancements (Kevin Seymour, [LP#1481894])
- Fix bugs with estimated dev time (Bryan Alsdorf, [LP#1494723])
- Display pretty error page for auth exceptions (Bryan Alsdorf)
- Fix POP3 download bug (Craig Pinfold, #66, #69)
- Add autosize plugin to all TEXAREAs (Elan Ruusamäe, #70)

[3.0.3]: https://github.com/eventum/eventum/compare/v3.0.2...v3.0.3
[LP#1481894]: https://bugs.launchpad.net/eventum/+bug/1481894
[LP#1494536]: https://bugs.launchpad.net/eventum/+bug/1494536
[LP#1494723]: https://bugs.launchpad.net/eventum/+bug/1494723

## [3.0.2] - 2015-08-04

This release highlights translatable history entries, CAS Auth Backend and lots of UI fixes.

- Fix sql error in disassociate custom field (Elan Ruusamäe)
- Fix cancel update issue action (Elan Ruusamäe, #47)
- Add XMLRPC method to upload files to issue (Elan Ruusamäe)
- RemoteApi: add checkAuthentication method (Elan Ruusamäe)
- RemoteApi: add getServerParameter method (Elan Ruusamäe)
- Fix opensearch template Smarty error caused by 09a1da1 (Elan Ruusamäe)
- RemoteApi: add getWeeklyReportData method to get weekly report data only (Elan Ruusamäe)
- Avoid associating issue with itself (Elan Ruusamäe, #29)
- Allow history entries to be fully translated (Elan Ruusamäe, #51)
- Add associated issues field to New Issue page (Elan Ruusamäe, #52)
- Added global setting to control if the description is used as email #0 (Bryan Alsdorf)
- Properly handle handleAssignment workflow method deprecation (Elan Ruusamäe, 539ef83, 8f4eb61, 130ec88, 827089e)
- Convert user preference timezone in abbreviation to timezone (Elan Ruusamäe, #53)
- Add select all button to edit notification dialog (Elan Ruusamäe)
- Use HTTP Referrer when switching projects (Elan Ruusamäe)
- Made "Expected Resolution Date" hideable on new issue page (Bryan Alsdorf)
- [Backwards incompatible change] Changed method signature for Workflow::preNoteInsert (Bryan Alsdorf)
- UI fixes #54, #55, #56, #57, #58, #59, #62, #63, #64, #65
- Add CAS Auth Backend (Bryan Alsdorf, #61)
- Strikeout inactive accounts on Stats page (Elan Ruusamäe)
- Allow customers to export data and only export visible fields (Bryan Alsdorf)
- Fix "Assignment: Array" bug in template when issue is assigned to multiple assignees (Elan Ruusamäe)

[3.0.2]: https://github.com/eventum/eventum/compare/v3.0.1...v3.0.2

## [3.0.1] - 2015-04-21

This release highlights are ajax based file uploads via dropzone and clone issue feature.
The MySQL driver for new installs is now mysqli, not deprecated mysql.

- Add option to set time summary when sending a note (Bryan)
- Optionally send reminders to a different IRC channel (Bryan)
- Fix broken Workflow::handleSCMCheckins call from 3.0.0pre1 (Elan Ruusamäe, GH#15)
- Display custom fields in CLI (Joffrey, GH#39)
- Notify the Notification List when creating an issue (Bryan)
- Add Clone Issue functionality (Bryan, GH#41)
- Add AJAX upload via dropzone library (Elan Ruusamäe, GH#25)
- Handle Eventum issue links as well for issue linking (Elan Ruusamäe, GH#15)
- Discard {literal} from templates (Elan Ruusamäe, 09a1da1)
- Use mysqli PEAR::DB driver in new installs (Elan Ruusamäe)
- Replace jpgraph 1.5.3 with phplot 6.1.0 (Elan Ruusamäe, GH#46)

[3.0.1]: https://github.com/eventum/eventum/compare/v3.0.0-pre1...v3.0.1

## [3.0.0-pre1] - 2015-02-03

Added DB layer to replace PEAR in the future.
Rework of XMLRPC code.
LDAP integration improvements.
SCM supports now multiple SCM systems.
Eventum CLI is now distributed as PHAR file.

- Make Custom Fields Weekly Report honor Project ID (Raul Raat, GH#6)
- Exclude removed notes when generating note sequence number (Bryan Alsdorf, Fixes [LP#1377921])
- Catch exception from invalid timezones and default to UTC (Bryan Alsdorf, GH#8)
- Remove duplicate key (Elan Ruusamäe, [LP#788699])
- Fix weekly report excluding last day (Elan Ruusamäe, [LP#898607])
- Package release assets with component (Elan Ruusamäe)
- Fixed Search for issues where I am in the notification list (Bryan Alsdorf, [LP#1201415])
- Modernize select multiple using jQuery-chosen (Elan Ruusamäe, GH#12)
- Build eventum.phar (CLI tool) (Elan Ruusamäe, GH#14)
- Add layer for database to replace PEAR DB in the future (Elan Ruusamäe, GH#13)
- Allow setting Expected Resolution Date from new issue form (Elan Ruusamäe)
- Admin/LDAP: fix ui warning when setting up initial config (Elan Ruusamäe)
- Admin/users: show user aliases in the listing (Elan Ruusamäe)
- Improved LDAP login with email aliases (Elan Ruusamäe)
- Added page class to body tag of popup (Bryan Alsdorf)
- SCM rewrite to support multiple SCM, improvements to svn hook (Elan Ruusamäe, GH#15)
- Rework of XMLRPC code (Elan Ruusamäe, GH#17, GH#19)
- Remove "Daily Tips" (Bryan Alsdorf, GH#20)
- Improve setup (Elan Ruusamäe, GH#21)
- Hide draft and email sections if no email account is setup (Bryan Alsdorf)
- Allow users to be given no project access (Bryan Alsdorf)
- Add "Add me to Notification List" button for customers (Bryan Alsdorf)
- Include issue submit date in issue close email (Elan Ruusamäe)
- Fix incorrect "last action type" when a user without an account sent an email (Bryan Alsdorf)
- Added notification email address for products (Bryan Alsdorf)
- Use DB query placeholders (Elan Ruusamäe, GH#26)

[3.0.0-pre1]: https://github.com/eventum/eventum/compare/v2.4.0-pre1...v3.0.0-pre1
[LP#788699]: https://bugs.launchpad.net/eventum/+bug/788699
[LP#898607]: https://bugs.launchpad.net/eventum/+bug/898607
[LP#1201415]: https://bugs.launchpad.net/eventum/+bug/1201415
[LP#1377921]: https://bugs.launchpad.net/eventum/+bug/1377921

## [2.4.0-pre1] - 2014-10-04

The templates have been ported to Smarty3, this is rewrite to use CSS for styling.
PEAR Date class has been replaced by PHP DateTime class, users having incompatible timezone, may need to set timezone again in their preferences.
This release uses Composer for PHP Class autoloader.

- Fixed bug with having multiple dynamic custom fields on a page (Bryan Alsdorf)
- Added "User Filter" functionality to LDAP integration (Bryan Alsdorf)
- Added "Product" filter to adv search page (Bryan)
- Changed close issue to default to sending to all users instead of internal only (Bryan)
- Changed HTTP Basic Auth handling in Auth::checkAuthentication() so it does not redirect (Bryan)
- Added Partner API to help make Eventum integrate with partners / external systems (Bryan)
- Automatically uncompress gzip files when using the "view" link (Bryan)
- Send notification on private file uploads to internal users (Bryan)
- Made workflow class also look in /local/workflow/ for backends (Bryan)
- Set SQL_MODE to '' in setup (Bryan)
- Added script to sort incoming emails/notes/drafts and route to correct method (Bryan)
- Allow customers to use the quick filters (Bryan)
- Added basic auth support to Auth::checkAuthentication() (Bryan)
- Added 'close_popup_windows' to Prefs::getDefaults() (Bryan)
- Check if session is already started before calling session_start() (Bryan)
- Added "Severity" field, this can be used with or instead of "Priority" (Bryan)
- Changed view page and template to better manage fields that can be disabled or selectively shown (Bryan)
- Look for templates in local directory before main directory so installations can be customized (Bryan)
- Fixed permission check problem for close issue page (Bryan)
- Changed "Custom Fields" to "Additional Details" on view issues page (Bryan)
- Deprecated workflow "handleAssignment" in favor of "handleAssignmentChange" (Bryan)
- Added workflow method to control if a user has access to update an issue (Bryan)
- Added "Group" to reminder conditions (Bryan)
- Allow ordering of resolutions (Bryan)
- Allow aliases to be used when requesting new password (Bryan)
- Changed FAQ entries to not popup in new windows (Bryan)
- Added "getActiveGroup" to workflow (Bryan)
- Added "Active Group" to reminder conditions
- Added 'Product' field. Eventually multiple products per issue will be supported (Bryan)
- Added Workflow::canSendNote() method (Bryan)
- Call Workflow::shouldEmailAddress() from Notification::notifySubscribers() (Bryan)
- Added "hasFeature" to Customer backend (Bryan Alsdorf)
- [CWE-276][CVE-2014-1631] disable setup when already configured (Elan Ruusamäe)
- [CWE-94][CVE-2014-1632] fix improper escaping of creating config file (Elan Ruusamäe)
- Added "checkbox" type to custom fields (Bryan)
- Fixed bug with emails not being sent when not associated with an issue (Bryan)
- Clean POST of unwanted characters, handle 4-byte unicode (Elan Ruusamäe)
- Hide products row on view issue page if no products are defined (Bryan)
- Added description as Email #0 (Bryan)
- Allow Workflow::getIssueIDforNewEmail to return customer and severity info (Bryan)
- Added $type parameter to Workflow::formatIRCMessage() (Bryan)
- Add option to "Separate Not Assigned to User" to weekly report (Raul Raat, GH#2)
- Replace "Only Status Changes" with "No time spent" in weekly report (Raul Raat, GH#3)
- Add lock support when updating issue details (Elan Ruusamäe, GH#4)
- Get rid of PEAR Date, use native DateTime in PHP (Elan Ruusamäe, [LP#684907])

[2.4.0-pre1]: https://github.com/eventum/eventum/compare/v2.3.4...v2.4.0-pre1
[CVE-2014-1631]: http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2014-1631
[CVE-2014-1632]: http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2014-1632
[CWE-276]: http://cwe.mitre.org/data/definitions/276.html
[CWE-94]: http://cwe.mitre.org/data/definitions/94.html
[LP#684907]: https://bugs.launchpad.net/eventum/+bug/684907

## 2.3.4 - 2013-11-28

- Fixed STR_PAD_LEFT PHP 5.3 compatibility issue. Info and fix from mailinglist (Elan Ruusamäe)
- Fixed custom_fields_weekly not encoding data properly for JavaScript (Elan Ruusamäe)
- Add logged in username in automated error reports (Elan Ruusamäe)
- Added X-Eventum-Priority header to outgoing emails (Elan Ruusamäe)
- Disable autocomplete on forms that fill somebody else's password (Elan Ruusamäe)
- Add host aliases support for notes and drafts as well (Elan Ruusamäe)
- Add custom fields to mail headers as X-Eventum-CustomField-<FieldName> (Elan Ruusamäe)
- Load local config for CVS/SVN integration from script dir (Elan Ruusamäe)
- Fix preg_replace warning, when someone managed to upload file containing slash in it's name (Elan Ruusamäe)
- Move SCM configuration to separate admin panel (Elan Ruusamäe)
- Handle cases when broken clients send out email with duplicate Message-Id headers. RFC2822 clearly states maximum count of one header allowed (Elan Ruusamäe)
- Make time tracking categories project specific (Elan Ruusamäe)
- API: Misc::escapeString can add quotes around strings (Elan Ruusamäe)
- fix bug for adding time entries is broken at the end of month Edit (#1186330)
- Add Back-Off functionallity (Petter Sandholdt) (GH#4)
- Redirect to issues listing when signing in (Elan Ruusamäe)
- Change downloading attachments encoding to urlencode (Elan Ruusamäe) (#1251335)
- Save eventum setup.php as PHP code, not base64 encoded data (Elan Ruusamäe)

## 2.3.3-RC3 - 2012-07-09

- Fixed bug #1021258 where users password is not hashed when they are initially created (Bryan Alsdorf)
- Made SCM commits section scrollable using CSS (and jQuery) (Elan Ruusamäe)

## 2.3.3-RC2 - 2012-06-05

- Added shouldAttachFile workflow method (Elan Ruusamäe)
- Bug fixes for schema file relating to customer and contact id (Bryan Alsdorf)

## 2.3.3-RC1 - 2012-05-30

- Added email association check (for unassociated emails) to monitor script (Elan Ruusamäe)
- Fixed bug with users not receiving notification of own actions (Bryan Alsdorf)
- Timetracking: do not move start or stop time if refreshing and duration not yet filled (Raul Raat)
- Add similar time tracking to post note window as there is in add time window (Elan Ruusamäe)
- Add possibility to set rank to issue resolutions so their order can be changed (Elan Ruusamäe)
- Added option to check if IRC bot process is still running (Bryan)
- Added pluggable auth user backends (Bryan, Elan Ruusamäe)
- Added LDAP user backend (Bryan)
- Added option to clear list filters by passing in url parameter view=clear (Bryan)
- Changed customer_id and contact_id to be strings (Bryan)
- Added "remember me" option to login (Elan Ruusamäe)

## 2.3.2-RC1 - 2011-12-03

- Remove unused HTTP and HTTP_Request, Benchmark_Timer classes from bzr (Elan Ruusamäe)
- Separate search methods to new Search class (Elan Ruusamäe)
- fix new users are creation groups (group id was 0, should be NULL) bug #806083
- fix XSS in user full name (bug #721785)
- fix location of block.t.php Smarty plugin for setup script (bug #809182)
- generate new Message-Id to auto-generated emails, instead of reusing original (bug #722862)
- Moved user preferences to a separate table (Bryan)
- Added preference to allow users to get copies of their own emails (Bryan)
- Changed view email window to display sequential email ID instead of raw ID (Bryan)
- Minor fixes to get Custom Field to work whose key is stringual, r4363 (Elan Ruusamäe)
- Update bundled jQuery to 1.6.4 (Elan Ruusamäe)
- Added option to notify specific IRC users and categorize messages (Bryan Alsdorf)
- Added new workflow method formatIRCMessage (Bryan Alsdorf)
- Port expandable cell to jQuery (Elan Ruusamäe)
- Updated PEAR packages to latest versions (Elan Ruusamäe)
- Added interface for Workflow classes to store arbitary workflow configuration in Setup (Elan Ruusamäe)
- Added sphinx fulltext search backend (Bryan Alsdorf, Elan Ruusamäe)

## 2.3.1 - 2011-02-10

- Improve getCustomFieldWeeklyReport (merge request 31659)
- Update Smarty 2.6.18 -> 2.6.26 (Elan Ruusamäe)
- Put 'Release' in the 'The issue was updated by' e-mail (Robbert-Jan Roos)
- Changed ereg to preg_match in template helper for compatibility (Bryan)
- Removed assignment by reference in db helper to prevent PHP warning (Bryan)
- Fix problem with custom field validation (Bryan) [LP#628862]
- Allow using GET parameters in reports/weekly.php (Elan Ruusamäe)
- Added workflow method to supply custom Link_Filter rules. Supports also callbacks (Elan Ruusamäe)
- Changed 'recieved' to 'received' (Robbert-Jan Roos)
- XSS Fix: escape issue_id in templates accessing it directly via {$smarty.get.id} (Elan Ruusamäe)
- Fix bug with ajax dynamic custom fields not honoring "hideWhenNoOptions" (Bryan Alsdorf) [LP#641133]
- Make attachment names linked in issue Initial Description (Elan Ruusamäe)
- Set memory limit to ~2GiB to be able to download 10MiB emails (Elan Ruusamäe)
- Use KiB, MiB keywords for filesizes (Elan Ruusamäe)
- Rework Mail_Queue code to be memory efficent by fetching only one email a time to memory (Elan Ruusamäe)
- Fix xmlrpc server missing global $XML_RPC_erruser (Elan Ruusamäe)
- Add better xmlrpc client class and sample (Elan Ruusamäe)
- Rewritten monitor script and class with modern code and flexible (Elan Ruusamäe)
- Support for configuring Monitor preferences (Elan Ruusamäe)
- Fix user group when creating new user (Elan Ruusamäe) [LP#691398]
- Allow translating 'Re: ' in email subjects (Elan Ruusamäe)
- Add 'Subscribe Me' button to issue details screen for quickly add user itself to default notification options (Elan Ruusamäe)
- Fix charset when processing Mime_Helper::fixEncoding (replaced by decodeQuotedPrintable)
- Recognize Italian reply prefix in mail subject (Harri Porten)
- Show summary of users time tracking if there are more than one user timetracking record (Raul Raat)
- Fixed bug with quoting email addresses when they are surrounded by < > (Bryan Alsdorf)
- Fixed XSS vulnerabilities in advisory ZSL-2011-4989 (Bryan, Elan Ruusamäe) [LP#706385]

[LP#628862]: https://bugs.launchpad.net/eventum/+bug/628862
[LP#641133]: https://bugs.launchpad.net/eventum/+bug/641133
[LP#691398]: https://bugs.launchpad.net/eventum/+bug/691398
[LP#706385]: https://bugs.launchpad.net/eventum/+bug/706385

## 2.3 - 2010-08-19

- Removed reference to dynCalendar.css (Bryan, #42301)
- Get default actions individualy for each address being added to notification list (Bryan)
- Fixed case sensitivity problem when excluding project aliases from being added to notification list (Bryan)
- Always allow issue reporter to email the issue (Bryan)
- Added manage aliases interface (Dave Anderson)
- Rip out Benchmark (Elan Ruusamäe)
- Call Workflow::handleNewIssue before Notifications are sent out (Elan Ruusamäe)
- Added ability to bulk close issues (Bryan Alsdorf)
- Consolidated code that auto added CC address to the notification list (Bryan)
- Added issue URL to issue auto created email (Bryan)
- Added admin page to manage email aliases (Dave Anderson)
- Added offline / maintenance screen displayed based on constant in config file. While offline, management pages are still accessible (CmputrAce)
- Display new issue notification when issue is moved (Bryan)
- Add Download Raw Email support to email view (Elan Ruusamäe)
- Fixed bug with upgrade process if short_tags is disabled. Fixed #42718 (Bryan)
- Allow using download_emails.php for routing and issue creation for same IMAP account (Elan Ruusamäe)
- Fixed problem with manage groups page in IE7 (Bryan)
- When loading user preferences always merge them with system defaults (Elan Ruusamäe)
- Fixed problem with CLI open-issues. Fixed #44238 (Bryan)
- Merged patch for anonymous user access from Bryan Petty [Tierra] with minor modifications (Bryan)
- Call Workflow::getAllowedStatuses from bulk update (Bryan)
- Added ability to set message when bulk closing an issue (Bryan)
- Use APP_DEFAULT_PAGER_SIZE on advanced search page. Fixes #45045 (Robbert-Jan Roos)
- Use System user ID when inserting an issue if no reporter is specified (Bryan)
- make bookmarkable custom filters via searchbar.php?custom_id=N (Elan Ruusamäe)
- Sort timezones alphabetically (Bryan)
- Added ability to sort by scheduled release (Robbert-Jan Roos)
- Remove call to set_magic_quotes_runtime() since it is deprecated in PHP 5.3 (Bryan)
- Added links to list.php to search for issues from same reporter or customer (Bryan)
- Added ability for Custom Field backends to use ajax to get options (Bryan)
- Fixed bug with abstract customer backend (Bryan)
- Added ability to update expected resolution date and custom fields on list issues page (Raul Raat)
- Filter for links issue history popup (Elan Ruusamäe)
- Misc files for Custom fields (Bryan Alsdorf)
- Allow disabling saving routed drafts/notes/emails if APP_ROUTED_MAILS_SAVEDIR constant undefined (Elan Ruusamäe)
- Add OpenSearch description to search issues by ID (Elan Ruusamäe)
- SCM commit hook reports back status that SCM server reports back (Elan Ruusamäe)
- Merge extra _GET and _POST params in searchbar.php?custom_id=N do be able to dynamically enchance saved custom search (Elan Ruusamäe)
- Prevent users with a role less than manager from logging into archived projects (Bryan Alsdorf)
- Make popup windows unique to issue, so creating two post note popups won't overwrite former ones (Elan Ruusamäe)
- Updated php-gettext up to 1.0.9 (Elan Ruusamäe)
- Added automatic lock file cleanup to download_emails.php (Erno Rigo)
- Check /etc/mailname for hostname to use in mail system (Peter Lieverdink)
- Fixed subject based routing to extract attachments from notes as well (Bryan Alsdorf)
- Moved crons to /crons/ (Bryan Alsdorf)
- Remove PEAR HTTP_Request from docs, no longer seems used (Elan Ruusamäe)
- Fixed problem with auth redirects failing in subdirectories (Bryan Alsdorf)
- Make Last Action Date translatable in issue list screen (Elan Ruusamäe)
- Added Custom Fields Weekly Report (Raul Raat)
- Allow setup to configure default timezone and start day of week (Elan Ruusamäe)
- Make default timezone and default start day of week configurable in setup (Elan Ruusamäe)
- Add new option to Custom Fields Weekly Report to show time spent by user (Raul Raat)
- Fixed bug with displaying help (Bryan)
- Fixed bug with dynamic custom fields (Bryan)
- Fixed formatting bug in email accounts admin page (Bryan)
- Fixed bug with fix-charset script (Bryan Alsdorf, Bug #42294)

## 2.2 - 2009-01-14

- Fixed few errors with template localization (Alessandro Ogier)
- Added Precedence: Bulk header to emails to prevent out of office replies. Fixes #34466 (Bryan)
- Replaced prototype JS library with jQuery JS library (Bryan)
- Added datepicker UI to date fields (Bryan)
- Added compatibility for MySQL 6 (Bryan)
- Added workflow method to determine what issue an email or note should be associated with (Bryan)
- Adding attachments to outgoing messages support (Harri Porten)
- Allow selecting initial project with /select_project.php?project=PROJECT_ID query parameter (Elan Ruusamäe)
- Fixed problem with last action date title not being set correctly (Bryan)
- Fixed problem where notes with attachments showed as "blocked" even though they were not (Bryan)
- Ignore wrongly formatted To headers instead of throwing an error (Bryan)
- Set SQLMODE to to blank (Bryan)
- Display link to attached file download in File Attached email notify (Elan Ruusamäe)
- Added new report to show a breakdown of issues by category and status (Bryan)
- Added support for user's having multiple email aliases. This is just a backend code, no UI to manage aliases (Bryan)
- Added new workflow method to modify messages being added to the mail queue (Bryan)
- [MAJOR] Changed email system to convert all incoming emails and notes to default charset before storing (Bryan)
- [MAJOR] Added system to manage database upgrades automatcially as SQL patch sets (Elan Ruusamäe)
- added convert-utf8.php script to update database to utf8 if the former encoding was proper (Bryan)
- added fix-charset.php script to update database to utf8 if the former encoding was improper (Elan Ruusamäe)
- Merged change to display issue created date on list issues page (Bryan)
- bounce emails back to user if download_emails.php method is used to handle routing and user has no permission to email to note or draft (needs "Leave Copy of Messages On Server" to be off) (Elan Ruusamäe)
- Fixed bug with user being able to update issues in projects he did not have access to (Bryan, #37320)
- Display in issue updated notifications name who made the change (Elan Ruusamäe)
- Added mechinism for workflow to set custom messages to be displayed in the UI (Bryan)
- Added new workflow methods preStatusChange, prePage (Bryan)
- Removed "View Mail Queue" link for Standard Users (Bryan, #37324)
- Changed subject of issue manually created from email to be consistent with other emails (Bryan)
- Allow managers to delete notes from any user (Bryan)
- Handled integer custom fields on advanced search (bug #38253)
- Added support for default values to the Custom Field API (Bryan)
- Don't check which project an email account belongs to when listing emails for an issue (Bryan)
- Fixed problem with associated issue validation on issue update page (Ingo van Lil, Bryan)
- Use text field for choosing issue to associate emails with (Bryan)
- Use text field for choosing duplicate issue (Bryan)
- Fixed bug with project selection not being remembered (bug #38279)
- Fixed bug with sorting by status from advanced search page (bug #37372)
- Fixed bug with adding attachments when sending emails (Bryan)
- Send specialized headers to reporters too (Bryan)
- Make `<title>` more informative in view.php pages (Elan Ruusamäe)
- Fix compatibility with PEAR Date 1.5.0, displaying times in wrong time (Elan Ruusamäe)
- Added pt_BR translation (Georger Araujo)
- Added workflow method to control which notification options are set (Bryan)
- Updated jquery ui.datepicker.js to 1.5.2 (Elan Ruusamäe)
- Added scm_log_url to SCM and handle ADDED and REMOVED actions for checkins (Elan Ruusamäe)
- Changed logic to allow users without access to the project to be CC'd on emails (Bryan Alsdorf)
- Updated datepicker to RC version to solve problem with the day of DST switch being displayed twice (Bryan Alsdorf)
- Allow users to login using email aliases (Bryan Alsdorf)
- Made week start day configurable from preferences for datepicker (Elan Ruusamäe)
- Let custom field size for multiple selection be items count but not more than 10 (Elan Ruusamäe)
- Fixed bug with encoding addresses with special characters when sending emails (Bryan)
- Fixed grp_id error on the manage users page after editing a user (Kirk Brown)
- Added workflow method to control if new To/Cc addresses are auto added to notification list (Bryan)
- Prevent PHP error with IRC bot when trying to fetch channels for a project with no channels defined (Bryan)
- Added more informative to close issue page explaining the difference between Internal / All notification options (Bryan)
- Fixed bug with custom field report display keys instead of values (Bryan, Elan Ruusamäe)
- scm/eventum-cvs-hook: support CVS 1.11 and 1.12 formats (autodetected if configured correctly) (Elan Ruusamäe)
- fix cronjobs erroneously report removed lock files. Fixes #904033 (Elan Ruusamäe)
- Add a simple screen on the issue page for amending the reporter (Elan Ruusamäe, Dave Anderson)
- Fix search returning deleted notes. Bug #788718 (Elan Ruusamäe)

## 2.1.1 - 2008-01-09

- Added missing PEAR classes for Text_Diff (Elan Ruusamäe)
- Fix unwanted breakage of PHP 4.x compatibility (Elan Ruusamäe)
- Include JavaScript files client side, not from Smarty. Fixes #32619 (Elan Ruusamäe)
- Avoid redefine error of APP_GETTEXT_MODE constant in language class (Elan Ruusamäe)
- Added 'Category' to workload by date range report (Bryan)
- Update php-gettext wrapper to support switching locales on same page (Elan Ruusamäe)
- Update php-gettext to find LC_MESSAGES from various dirs like glibc function does (Elan Ruusamäe)
- MIME decode attachment filenames from emails properly (Elan Ruusamäe)

## 2.1 - 2007-11-20

- Fixed error with DB error when removing assignnees from issue assignment list (Bryan)
- Rewritten error handling to create less smaller error reports (Elan Ruusamäe)
- Make issue associated list as text field (Bryan, Elan Ruusamäe)
- Implementing per project "mail aliases" (Alessandro Ogier, Elan Ruusamäe)
- Rollback file upload if there was an error instead of creating lingering attachments (Elan Ruusamäe)
- Made timetracking window input more convenient (Elan Ruusamäe)
- Display email addresses to whom email was sent when issue was updated (Elan Ruusamäe)
- Remove unnecessary array_map that breaks UTF-8 encoding in charts legend (Grzegorz 'Dzikus' Sterniczuk)
- Propagate errors from invalid to header and ignore it in emails listing page (Elan Ruusamäe)
- Add "Show Times spent on issue" to weekly report page (Elan Ruusamäe, Raul Raat)
- Fixed bug with viewers updating preferences (Bryan)
- Improved Weekly Reports output possibilities (Raul Raat)
- Added more information to Workflow::shouldEmailAddress (Bryan)
- Fixed bug with adding warning message to base64 encoded email (Bryan)
- Added "Recipients" to view email and view note page (Bryan)
- Added separate columns for different custom field datatypes (Bryan)
- Display database error in text mode when invoked from cron (Elan Ruusamäe, Raul Raat)
- Hide issue stats from reporters when "Segregate Reporters" is enabled (Bryan)
- Fixed bug with lookup layer on edit notification list (Bryan)
- Fixed bug with reminders when no recipients are found (Bryan)
- Fixed bug with emails downloaded from mail server only being sent to issue assignee (Bryan)
- Added support level to list issues page (Bryan)
- Fixed conditional statements involving roles and localization (Bryan)
- Update add time tracking window so that change of start time changes the end time (Raul Raat)
- Don't hide Total Time Spent: when hiding time tracking block in issue details page (Raul Raat)
- Added ability to control if a custom fields is required and validation options from backend (Bryan)
- Added option to include custom fields on close issue page (Bryan)
- Added more parameters to Customer::notifyIssueClosed() (Bryan)
- Call Workflow::getAdditionalEmailAddresses() when notifying an issue has been updated (Bryan)
- Add extra parameter to Workflow::getAdditionalEmailAddresses() to allow issue diffs to be passed (Bryan)
- Fixed bug with not encoding `[` and `]` in address strings (Bryan)
- Changed roles needed to move issues between projects (Bryan)
- Added option to hide closed issues on stats page (CmputrAce)
- Make variable available for workflow to be able to detect whether the email created new issue (Elan Ruusamäe)
- Added support for inactive options to Custom field backends (Bryan)
- Fixed bug with saving routed email (Bryan)
- Added workflow method to control if email addresses are automatically added to the notification list (Bryan)
- Fixed bug with blocked emails adding sender to the notification list (Bryan)
- Changed customer API to support multiple contracts (Bryan)
- Fixed bug with closed issue notification going out even when it shouldn't have (Bryan)
- Prevent users of being notified if they assign an issue to themselves (Bryan)
- Updated Smarty and PEAR packages to latest versions (Elan Ruusamäe)
- Fixed tab order of custom fields (Bryan)
- Fixed bug with expandable tables (Bryan)

## 2.0.1 - 2007-04-17

- Fixed packaging bug that prevented setup from running (Bryan)

## 2.0 - 2007-04-12

- Fixed bug with user recieving an update email when they updated the issue (Bryan)
- Fixed bug with inserting attachment from email with an apostrophe in the email name (Bryan)
- Changed support_email table to not truncate long to and cc lists (Bryan)
- Changed status graph colors to actually match the status colors (Bryan)
- Fixed bug with downloading notes multiple times (Bryan)
- Automatically add sender of email to authorized repliers list when auto creating an issue (Bryan)
- Refresh parent window when closing notification popup (Bug #20020) (Bryan)
- Changed Eventum to honor default notification options when adding an address from an email (Bryan)
- Fixed bug with special "bullet" character when submitting an issue (Bryan)
- Added new 'Estimated Development Time' report (Bryan)
- Fixed bug that allowed reporter to change the status when sending an email (Bryan)
- Fixed bug with closing an issue while switching projects in another window (Bryan)
- Fixed PHP error on associate email page (Bryan, Rusty Nejdl)
- Changed Advanced Search to allow reporters to access it (Bryan)
- Added X-Eventum-Assignee header to emails (Bryan)
- Fixed bug with date fields on advanced search page not showing correctly (Bryan)
- Fixed bug with message body not being displayed when Content-Type is missing (Bryan)
- Fixed PHP warning in customer backend (Bryan)
- Fixed bug caused by whitespace at start of email address (Elan Ruusamäe)
- Fixed bug with wrong users being displayed on close issue page (Bryan)
- Changed quick filter form to be fixed width to avoid rendering problems in Firefox (Bryan)
- Fixed bug with error when sending emails creating infinite loops (Bryan)
- Fixed bug with special "dash" character when submitting an issue (Bryan)
- Fixed problem with rss feeds searching by custom fields (Bryan)
- Worked on removing instances of call time pass by reference (Bryan)
- Fixed bug with subject based routing with special characters (Bryan, Thanks to Frank Tilmans)
- Fixed bug with saving mail queue log (Bryan, Thanks to Peter van den Heuvel)
- Fixed bug with email bounces causing infinite mail loop when auto creating issues (Bryan)
- Added workflow method to be called before email is downloaded (Bryan)
- Added "Open Issues By Reporter" report (Bryan)
- Fixed bug with UTF8 characters on graphs (Bryan)
- Send emails with charset specified in config (Bug #17267) (Bryan)
- Added link from reporter to show all issues reported by that user (Bryan)
- Correctly show changes to issue private status in history (Bryan)
- Set last response date if sending an email from a user with a role of Standard User or above (Bryan)
- Added --separate-closed argument to Eventum CLI weekly-report command (Elan Ruusamäe)
- Removed double emails decoding bug (Bryan)
- Fix corruption of SCM commits messages of certain commit messages (Elan Ruusamäe)
- Allow SCM commit messages contain multiple issue IDs (Elan Ruusamäe)
- Add "Add signature" feature to issue close page (Elan Ruusamäe)
- Customize list page sorting order per column type (Elan Ruusamäe)
- Added $note_id to handleNewNote workflow method (Elan Ruusamäe)
- Detect and log corrupted MIME emails (Elan Ruusamäe)
- Show attached xml files inline (Elan Ruusamäe)
- Improved time tracking output to show also hours and days (Elan Ruusamäe)
- Changed view issue page to be more compact (Eliot Blennerhassett, Elan Ruusamäe)
- Rewritten SCM configuration which allows simple way of using https scheme (Elan Ruusamäe)
- Speedup SCM commits that are not associated with Eventum issue (Elan Ruusamäe)
- Optimized regex and memory usage (Elan Ruusamäe)
- Change permissions to allow Administrators to delete attachments from any users (Bryan)
- Remove "Remember Login" checkbox since it is confusing and mislabeled (Bryan)
- Fixed bug with custom fields report showing data across projects (Bryan)
- Fixed bug with Custom Fields with a type of date (Dave Greco)
- Dislay all recipients for emails in issue details screen (Elan Ruusamäe)
- Fixed bug with attachments breaking with magic_quotes_sybase = On (Dave Greco)
- Fixed stylesheet bugs (Dave Greco)
- Automatically add addresses on to/cc list to notification list when auto creating a new issue (Bryan)
- Change weekly report to be project specific (Bryan)
- Added warning message when closing issue from update issue page (Bryan)
- Added Ignore Issue Status Changes to weekly report window (Elan Ruusamäe)
- Update Eventum core code to handle PHP configurations without old style of globals (Elan Ruusamäe)
- Update JPGraph to handle PHP configurations without old style of globals (Bryan)
- Fixed bug with not setting the issue ID in the subject of emails sent to users not on the notification list (Bryan)
- Allow IRC bot to join server with username/password optionally (Elan Ruusamäe)
- Set system user to always have a role of administrator (Bryan)
- Fixed bug with displaying note sequence (Bryan)
- Fixed bug with extracting attachments (Bryan)
- Added Finnish translation (Jyrki Heinonen)
- Fixed bug attachment history entry not being added due variable overwrite (Elan Ruusamäe)
- Fixed detection in `process_{svn,cvs}_commits` whether there was issue id specified in commit message (Elan Ruusamäe)
- Order weekly report by Issue ID (Bryan)

## 1.7.1 - 2006-03-31

- Fixed bug with Workflow::handleAssignmentChange() being called too often (Bryan)
- Fixed bug that allowed unassigned issues even if "Allow unassigned issues" is set to no (Bryan)
- Added information on what community users should do to contribute code to the Eventum project (João)
- Fixed bug that tried to set status to "Assigned" when an issue was created with assignees (Bug #16165) (Bryan)
- Fixed bug with sorting by last action date with MySQL 5 (Bryan)
- Fixed bug with workflow API when updating custom fields (Bryan)
- Changed issue ID field to automatically strip non numeric characters when looking up issue (Bryan)
- Fixed bug that was causing too many redirects (Bryan)
- Added X-Eventum-Category special header (Bryan)
- Added workflow method to check if a user can email an issue (Bryan)
- Fixed bug where statuses were not restricted on view issue page (Bryan)
- Fixed bug with not encoding title on RSS feed (Bryan)
- Added favicon (Contributed by Georger Araujo)
- Added new constant APP_COOKIE_URL (Bryan)
- Fixed bug with using authentication when sending mail (Bryan)
- Fixed bug with empty reply-to headers causing mail to be associated with the wrong issue (Bryan)
- When creating a new issue from an email, add the senders to the authorized repliers list (Bryan)
- Added option to send closing comments to all users (Bryan)
- Fixed bug with custom date fields on anonymous report form (Bug #17166) (Bryan)
- Added new special header 'X-Eventum-Project' (Bryan)
- Added workflow method for when a user is added to the Authorized repliers list (Bryan)
- Added feature to allow sorting by custom fields (Bryan)
- Added full path to reports link (Bug #17551) (Bryan)
- Fixed bug with searching on custom date fields (Bryan)
- Changed naming format of saved routed emails/notes/drafts to be easier to read (Bryan)
- Fixed bug with issues not being created when using elipsis in issue description using Internet Explorer (Bryan)
- Fixed bug with notification email showing wrong status when sending a note (Bryan)
- Fixed bug with searching by keyword on email page (Bryan)
- Fixed bug with showing old project name when auto switching projects (Bryan)
- Added ability to use different hashing method for passwords (Bryan)
- Allow issue auto creation to work with subject based routing (Bryan)
- Fixed subject based routing to work across multiple projects (Bryan)
- Fixed page refreshes to use the relative URL instead of the absolute URL (Bryan)
- Fixed subject encoding in mail queue (Elan Ruusamäe)
- Truncate issue list in issue lookup to 70 characters (Elan Ruusamäe)
- Changed recent activity report to open issue links in blank windows (Elan Ruusamäe)
- Changed file upload window to not automatically close if there was an error uploading the file (Elan Ruusamäe)
- Changed templates to display 'Add Email/Note/Draft/etc' button, even when section is collapsed (Elan Ruusamäe)
- Fixed bug that prevented "Record Time Worked" section from being displayed (Elan Ruusamäe)
- Changed mail handling routines to be case insensitive (Elan Ruusamäe)
- Changed link filters to match more links and email addresses (Elan Ruusamäe)
- Fixed bug with associate emails page not remembering search parameters (Bryan)
- Changed configuration structure to separate directories / files (Bryan, Elan Ruusamäe)
- Changed default to "Allow un-assigned issues" to "Yes" (Bryan)
- Enabled email integration by default (Bryan)
- Prevent users with a role of reporter from accessing time tracking information (Bryan)
- Display multi-combo custom fields on custom fields report (Bryan)

## 1.7.0 - 2005-12-29

- Added feature to support custom fields with dynamic option lists (Bryan)
- Fixed bug with highlight quoted replies plugin with handling line separators (Bryan, Elan Ruusamäe)
- Fixed bug with displaying values from multiple option custom fields (Bug #12494) (Bryan)
- Added feature to allow custom fields to store date values (Bryan)
- Added URL parameter to specify the project to switch to when loading a page (Bryan)
- Added constants to allow default user preferences to be configured (Bryan)
- Fixed bug with error checking on anonymous report form (Bryan)
- Changed Authorization code to redirect using 'Location' header for all servers except IIS (Bug #13051) (Bryan)
- Changed FAQ screen to use created date when last updated date is empty (Bryan)
- Changed associate note feature to not change subject when associating with an issue (Bryan)
- Fixed bug with updating priorities (Bryan)
- Fixed bug with parsing multiple or invalid email addresses (Bryan)
- Fixed the SCM checkin code to properly update the last action date field for an issue (João)
- Fixed a problem with the IRC bot that would prevent it from working under PHP5 (João)
- Fixed a bug in which inactive users would still show up when sending emails and choosing Cc: recipients (João)
- Added a workaround to a Windows-only Time zone related bug that would trigger a crash in Apache in certain circumstances (João)
- Fixed bug with expandable tables on recent activity report (Bryan)
- Fixed bug on custom fields report that prevent custom fields with backends from being displayed (Bryan)
- Updated Eventum to be compatible with MySQL 5.0 (Bryan)
- Added feature to add attachments from notes as internal only files (João)
- Added Subject based routing (Bryan; special thanks to Tibor Gellert)
- Added feature to allow recipient flags on all notes/emails sent from Eventum even if routing is disabled (Eliot Blennerhassett, Bryan)
- Added feature to handle email messages that don't have a Message-ID header set (Bryan)
- Added new 'Stalled Issues' report (Bryan)
- Updated the fulltext search routine to properly use UNIONs and allow MySQL to use the proper indexes when searching (João)
- Fixed bug on the RSS feed script to avoid an error condition when no issues could be found for a particular saved search (João)
- Fixed bug that reset Administrator's permission level (Bryan)
- Changed list.php to use relative instead of absolute URL (Bryan)
- Fixed bug with custom fields not showing up on new issue email from anonymous report form (Bryan)
- Added new workflow method to notify additional email addresses when a new issue is created (Bryan)
- Fixed bug with updating custom fields for projects with single quotes in their name (Bryan)
- Added code to prevent caching of csv export page (Bryan)
- Added priority and category to bulk update (Bryan)

## 1.6.1 - 2005-08-19

- Fixed the installation procedure to add the INDEX privilege to the MySQL user (João)
- Fixed bug with handling HTML characters in Internal FAQ entries (Bryan)
- Fixed bug displaying priority in current filters (Bryan)
- Added feature to set X-Eventum-Type header in new assignment email (Bryan)
- Fixed bug that allowed users to access attachments, custom fields, phone calls and time tracking from issues they did not have access too (Bryan)
- Added new workflow method to check if an address should be emailed (Bryan)
- Fixed the issue searching routine to properly handle disabled fulltext search and customer integration features (João)
- Improved the IRC Bot script to be easier to configure (João)
- Added feature to update issue assignment, status and release for multiple issues at the same (Bryan)
- Fixed labels on workload by date range graphs (Bryan)
- Added feature to highlight quoted replies in notes and emails using smarty plugin from Joscha Feth (Bryan)
- Updated the bundled XML-RPC library to the latest PEAR 1.4.0 release (João)

## 1.6.0 - 2005-07-29

- Added feature to control order of custom fields (Bryan)
- Added feature to specify custom field backend (Bryan)
- Added feature to control which users can access specific custom fields (Bryan)
- Improved fulltext search feature to include custom fields (Bryan)
- Fixed bug with returning list of statuses in abstract workflow backend (Bryan)
- Added reporter to advanced search page (Bryan)
- Fixed the editing of news items on the administration interface (João)
- Fixed possible SQL injection vulnerability on the Authentication class (Bug #12254) (João)
- Fixed the installation procedure code to properly detect MySQL versions and enable the fulltext search feature (João)
- Fixed possible SQL injection vulnerabilities on the Release and Report classes (Bug #12254) (João)
- Fixed bug that caused custom field data to be deleted from all projects when removing a field from one project (Bryan)
- Added the CREATE, DROP and ALTER privileges when creating a new MySQL user for the Eventum database (João)
- Added feature to display which filters are active on the issue listing screen (Bryan)
- Replaced JSRS library with a new httpClient library (Bryan)
- Fixed a bug that would prevent the authorized repliers list from working correctly (João)
- Changed the project switch feature so that it respects the user preference to auto close the popup window or not (João)
- Added the ability to rank FAQ entries (João)
- Added the feature to search for past releases on the advanced search screen (João)
- Fixed bug that caused URLs in news item to be corrupted (Bryan)
- Added option to choose time category when adding a time tracking entry from a note (Bryan)
- Added feature to automatically set the subject of new notes (Bryan)
- Fixed the view note window to properly display a special message when a note has been deleted (Bryan)
- Added feature to display a sequenential note number in title window of view note page (Bryan)
- Added feature to customize the boilerplate text of reminder alert messages (João)
- Fixed the RSS feature of custom filters to behave properly under Microsoft IIS (João)

## 1.5.5 - 2005-06-26

- Fixed the issue details page to properly escape the summary of associated issues (Bug #10464) (João)
- Fixed the link activation code to properly parse and ignore certain words (Bug #10263) (João)
- Added a feature to automatically enable/disable the full-text search feature on the installation procedure (João)
- Improved the installation routines to properly display the full path to potential missing files (João)
- Updated Example Customer API to handle expired customers (Bryan)
- Fixed bug that caused links in FAQ entries to be mangled (Bryan)
- Fixed a bug on the workflow API so that it will only list backend files with filenames ending in .php (Elan Ruusamäe)
- Added a check on the link filter feature to avoid double parsing for urls (Elan Ruusamäe)
- Fixed bug with full-text searching under MySQL 4.1 (Bryan)
- Fixed email routing where domain portion was not properly verified (Elan Ruusamäe)
- Added Expected Resolution Date field to list issues page (Bryan)
- Changed the recent activity report to properly escape values in query (Bryan)
- Fixed issue summaries escaping on weekly report to prevent XSS (Elan Ruusamäe)
- Fixed bug that that didn't mark issue as updated when adding a time entry (Bryan)
- Fixed bug with CLI command 'open-issues' (Bryan)
- Fixed the database schema file to properly set the table types to MyISAM (João)
- Merged the fix for the security hole on the PEAR XML_RPC package (João)
- Fixed the custom field handling code to properly escape HTML values (João)
- Fixed the advanced search screen to properly save the 'authorized to email' / 'notification list' options (João)
- Added a validation check to the installation screen for the sender address (João)
- Changed the preferences screen to not allow customers to edit their personal details (João)
- Removed references to the missing 'cst_use_fulltext' database field (João)
- Fixed the auto-link feature to properly recognize URLs with pipes in them (Elan Ruusamäe)
- Added a new Workflow API method to be triggered when SCM commits are made (Elan Ruusamäe)
- Fixed the IRC bot to automatically re-join the channels when it reconnects (João, Elan Ruusamäe)
- Improved the Workflow::handleIssueClosed API to receive all arguments related to an issue being closed (Elan Ruusamäe)
- Fixed bug with spell checker (Bryan)

## 1.5.4 - 2005-06-06

- Fixed bug with 'reply' button having a hard coded email account ID (Bryan)
- Added workflow method be to be called when adding a user to the notification list (Bryan)
- Fixed bug that prevented releases scheduled for today from showing up (Bryan)
- Added conditional statement to prevent PHP error if a user did not have any preferences set when creating new issue (Bryan)
- Changed the code to automatically disable magic_quote_runtime to prevent problems from creeping up (João)
- Changed the error handling routines to avoid sending out an email notification when the error is about MySQL's max_allowed_packet setting (João)
- Added workaround to prevent email from iNotes from being displayed as one line (Bryan)
- Fixed bug with < and > not showing up issue summaries on associate issues page (Bryan)
- Added feature to allow reporters to be added to the authorized repliers list (Bryan)
- Fixed bug with saving searches with the Rows Per Page as 'ALL' (Bryan)
- Changed send forms to display notification list accurately and consistently (Bryan)
- Added feature to mark last action as 'User Response' if a user with a level of 'Reporter' emails an issue (Bryan)
- Added feature to allow reporters to change 'Automatically close confirmation popup windows' preference (Bryan)
- Added check if 'register_argc_argv' is enabled in download_emails.php (Bryan)
- Fixed bug so that 'Remember Project' checkbox is honored on the select project page (Bryan)
- Added feature to automatically activate links within custom fields (Bryan)
- Added ability to change status when sending notes (Bryan)
- Added feature to display who closed the issue when sending notification email (Bryan)
- Added feature to automatically change pages to main page when switching projects from view or update page (Bryan)
- Fixed bug to only display FAQ entries for the currently selected project (Bryan)
- Fixed a bug that would trigger a loop of errors when a database connection cannot be completed (Elan Ruusamäe)
- Fixed the email removal routine to properly remove the associated email bodies (João)
- Fixed the permanent email removal routine so it doesn't remove the messages from the server if the 'leave messages on server' option is enabled (João)
- Added full text searching to the issue listing screen (Bryan)
- Fixed the issue details page to properly hide the custom fields table if there are none to be displayed (João)
- Fixed bug to prevent customers from accessing the email listing page (Bryan)
- Fixed SQL error when auto creating issue from email with no customer specified (Bryan)
- Changed the graphs on the initial screen to hide entities that don't have any values (João)
- Added a feature to display the number of open/closed items on the stats screen (João)
- Changed the user management screen not to allow administrators from changing the role of a customer user (João)
- Fixed the notification code to use a more descriptive subject about an issue being created from an email (Bryan)
- Fixed small time formatting bug that would only be triggered for values bigger than a day (João)

## 1.5.3 - 2005-04-21

- Fixed bug with segregate reporters that allowed reporters to access issues they didn't report (Bryan)
- Fixed problem with resetting user permissions when updating a project (João)
- Fixed bug with returning number of rows on list issues page (Bryan)
- Fixed bug with encoding certain characters in email addresses (Bryan)
- Fixed bug with timezone DST information for 'Europe/Tallinn' and 'Europe/Vilnius' timezones (Elan Ruusamäe)
- Increased the default memory limit on the IRC bot code to prevent crashes (Elan Ruusamäe)
- Increased the default memory limit on the IRC bot code to prevent crashes (Elan Ruusamäe)
- Improved module name readability by making module/directory name not wrap on the list of CVS checkins (Elan Ruusamäe)
- Changed recent activity report to properly fix the encoding of sender/recipient headers (Elan Ruusamäe)
- Added feature to automatically activate links from within attachment descriptions (Elan Ruusamäe)
- Fixed bug that prevented URLs like http://example.com/~user/ from being auto-linked (Elan Ruusamäe)
- Fixed mail queue log screen to properly use the user's preferred timezone when displaying the queued date (Elan Ruusamäe)
- Fixed date handling code to properly use PEAR::Date to convert timezones (Elan Ruusamäe)
- Changed textarea height size to fit within the send email popup window (Elan Ruusamäe)
- Changed the issue listing screen code so that sorting by status will use the status rank field (João)
- Fixed code that allowed one to associate an issue to itself (João)
- Added some extra checks to the login screen to properly report problems on the Eventum installation (João)
- Added some code to properly identify closed issues when displaying duplicate or associated issues (João)
- Fixed problem that prevented search options from being saved on the advanced search screen (Bug #10026) (Bryan)
- Fixed magic quote problem by auto-unescaping quotes on `$_REQUEST` array (Bug #9915) (Bryan)
- Allow reporters to access issues they are on the authorized repliers list when segregate reporters is enabled (Bryan)

## 1.5.2 - 2005-04-15

- Fixed the note viewing screen to prevent users with permission levels lower than "Standard User" from displaying notes (Bug #9134) (João)
- Fixed the time tracking remove routine to check if the person removing the entry is really its owner (Bug #9137) (João)
- Fixed the issue assignment feature of the listing screen to work again (João)
- Fixed bug that was causing php error when removing all assigned users from an issue (Bryan)
- Fixed bug with searching by date range on recent activity report (Bryan)
- Removed update issue confirmation dialog for users with a role of developer or above (Bryan)
- Fixed JS error on close issue page (Bryan)
- Fixed bug in example customer API (Bryan)
- Fixed bug with 'My Assignments' not remembering sort order (Bryan)
- Fixed bug #9181: Edit Notification List doesn't select address to edit (Bryan)
- Added feature to allow issue/note/draft routing to use normal email accounts instead of specialized setup (Bryan)
- Changed statuses to always be sorted by rank (Bryan)
- Fixed bug with array_merge() on manage users page (Bryan)
- Fixed bug with not being able to un-assign inactive users from issues (Bryan)
- Added more thorough input checking to prevent possible SQL Injection attacks (Bryan)
- Fixed Misc::activateLinks() method to handle links with tildes (Elan Ruusamäe)
- Fixed potential SQL injection vulnerabilities (Bryan)
- Fixed email handling code to properly strip CC and BCC headers from outgoing emails to avoid sending duplicate messages (Bryan)
- Added feature to list issues on Custom Fields report (Bryan)
- Added To and From columns to phone support listing (Bryan)
- Fixed estimated dev time showing up as minutes instead of hours on notification email (Bryan)
- Fixed a bug that was preventing an email from being converted to an issue even when it isn't from a known customer (João)
- Added extra order by clauses to make sure results are returned the same way every time (Bryan)
- Added feature to display project name in IRC notice if multiple projects are broadcasting in the same channel (Bryan)
- Fixed bug that caused notification to be sent to user who updated issue (Bryan)
- Fixed bug that prevented multiple select custom fields to have values cleared (Bug #9853) (Bryan)
- Changed issue listing screen show/hide links to be displayed in Opera/Safari (Elan Ruusamäe)
- Fixed dynCalendar so it works in Opera (Elan Ruusamäe)
- Added issue description to RSS feed as well as other minor fixes (Elan Ruusamäe)
- Fixed bug with transferring non-ASCII data over xmlrpc (Elan Ruusamäe)
- Fixed bug on the issue listing screen that would not add the assignee to the notification list (João)
- Added feature to clear closed date and resolution when re-opening issues (Bryan)
- Added feature to honor default notification options (Bryan)
- Added feature to display different auto created email for users that don't have accounts (Bryan)
- Added extra check to the installation procedure to properly check for session support (João)
- Fixed bug that caused the wrong timezone short name on daylight savings time to be displayed (João)
- Fixed bug that prevented SMTP authentication from working in a few special cases (João)
- Fixed problem that was triggering Internet Explorer's warning message about switching from secure to insecure mode on the reporting system (João)
- Added feature to automatically set the project lead user as a manager for that project (João)
- Fixed bug that caused release changes not to show up in update email (Bryan)
- Added feature so route emails script can now figure out what email account to use automatically (Bryan)

## 1.5.1 - 2005-03-11

- Fixed bug in which associating an email to a new issue with a quotation mark on the subject would break the summary input tag (João)
- Avoid displaying PHP warnings when running Eventum under safe_mode (João)
- Fixed the mail queue processing code that was referencing a missing method name (João)
- Added feature to replace special characters Microsoft Word uses for double and single quotes with normal characters when creating an issue (Bryan)
- Added feature to allow emails to be moved between accounts (Bryan)
- Added some form validation to the custom field report (João)
- Changed the attachment handling code to handle certain attachment types better (Bryan)
- Changed the issue/email listing screens to save their search related information in the database, instead of in cookies (João)
- Added indexes to a few columns (Bug #7676) (Bryan)
- Added some code to prevent people from creating an internal FAQ entry without selecting the project first (João)
- Added feature to allow download_emails script to be called via the web (Bryan)
- Fixed the issue update code to properly subscribe new assignees to the notification list (João)
- Changed the behavior of the view issue screen to automatically hide tables without any data (João)
- Fixed some caching problems that might be triggered when customizing the columns to be displayed at a project level (João)
- Fixed the report form so that it dynamically focus the correct field depending on what form fields are hidden (João)
- Changed the view email screen to set the page character set to whatever is set on the underlying email content (Elan Ruusamäe)
- Added Estimated Dev time field to list issues page, view issue page and update page (Dustin Sias)
- Added Percent complete field (Dustin Sias)
- Changed javascript confirmation when updating an issue to not be displayed if no emails accounts exist yet (Bryan)
- Fixed bug that allowed any authenticated user to assign any issue to any user (Bug #9097) (Bryan)
- Changed history of changes screen to properly decode quoted-printable subject (Elan Ruusamäe)
- Changed the expandable cell feature to also automatically activate links (Elan Ruusamäe)
- Changed the error handling routines to save an error log with more detailed information (João)
- Fixed bug that would prevent users from downloading files because of encoded content-type headers (João)

## 1.5 - 2005-03-01

- Fixed bug with looking up addresses not working when replying to email (Bryan)
- Fixed bug with APP_TITLE not being displayed in issue auto created messages (Bryan)
- Fixed Clock-In / Clock-Out link on non-base directories (João)
- Fixed the mail queue handling code to prevent displaying a PHP warning (João)
- Fixed a problem on the example customer backend that was triggering a DB error (João)
- Added missing function to Abstract_Workflow_Backend class (Bryan)
- Display the current textarea value when trying to update the custom fields (João)
- Issue assignment emails now go out from project email address (Bryan)
- Back button on mail queue log page uses app_base_url now (Bryan)
- Users can now have separate roles per project (Bryan)
- Added option to hide priority and file field on create issue page (Bryan)
- Drafts are now never deleted (Bryan)
- Issues Descriptions are now collapsible (Bryan)
- Added #s to drafts, notes, phone calls, emails and time tracking entries (Bryan)
- Weekly report excludes notification and authorized replier actions (Bryan)
- Upgraded recent activity report to handle emails, notes, drafts and time tracking entries (Bryan)
- If text file is > 5K force download instead of displaying (Bryan)
- Add confirmation if you do not redeem incidents when closing an issue (Bryan)
- Fixed tab order on new issue form (Bryan)
- Add attachments to new issue notification (Bryan)
- Remove certain CC addresses from incoming emails (Bryan)
- Added IRC bot restart script (Bryan)
- Fixed SQL error with advanced search (Bryan)
- Fixed bug with link filters (Bryan)
- Added option to add time tracking entry from close issue page (Bryan)
- Fixed typo in JS confirmation when sending an email (Bryan)
- Added option to let reporters only view issues they reported (Bryan)
- Forced timezone library to realize all dates in Eventum are stored as GMT (Bryan)
- Added option to mark issues as private (Bryan)
- Fixed bug with remembering the row count on the 'My Assignment' link (Bryan)
- Fixed bug that caused multiple blank file upload fields to appear (Bryan)
- Fixed bug that prevented the edit custom fields window from automatically closing (Bryan)
- Remove 'Return-Path' header from messages added to the mail queue (Bryan)
- Added option to display reporter to list issues page (Bryan)
- Added the feature to always allow the issue reporter to send emails (Bryan)
- Changed attachment handling to work with inline attachments (Bryan)
- Apply encoding fixes in more places (Elan Ruusamäe)
- Fixed the CVS integration code to silence console errors when adding a new directory (Elan Ruusamäe)
- Fixed the email download code to release the lock if there is an error connecting to the mail server (Bryan)
- Added workload by date range report (Bryan)
- Added missing "scm_checkin_associated" history type (João)
- Fixed bug on CVS integration script that was not encoding the URL arguments (João)
- Added the ability to rank custom priorities (João)
- Fixed bug when sorting by category (Bryan)
- Added specialized headers to outgoing emails (Bryan)
- Added new CLI command 'takeIssue' (Bryan)
- Added reminders and ability to sort to recent activity report (Bryan)
- Added feature to prevent time tracking categories 'Email Discussion' and 'Telephone Discussion' from being removed (Bryan)
- When changing status with 'Change Status' select box, send out notification message (Bryan)
- Fixed the CVS commit handling regular expression to properly match "issue" or "bug" followed by a number (Elan Ruusamäe)
- Fixed the monitor code to properly escape the dash when searching for the IRC bot pid (Elan Ruusamäe)

## 1.4 - 2005-01-04

- Fixed the notification code to properly handle the condition in which the recipient type flag is empty (João)
- Fixed the MIME handling code to support inline attachments (João)
- Fixed some of the navigation links so they show up for the Reporter permission level users (João)
- Fixed bug where developer role could not see which issues were quarantined from the list issues page (Bryan)
- Changed the mail queue code to properly add a Date: header to outgoing emails (João)
- Renamed the Profile page to Stats (João)
- Fixed the issue quarantine code so that when its status is changed it will now save a history entry about it (João)
- Changed the lookup field javascript code to search for keywords in the middle of the words instead of just at the beginning of them (João)
- Improved the error handling routine to also include the browser information (João)
- Fixed the email routing interface to allow 'issue+1@example.com' as a valid address (João)
- Changed the session code as to prevent it from messing up the browser cache (João)
- Removed the Lock/Unlock issue feature since it wasn't really restricting anything (João)
- Added 'Link Filters' so text matching a regular expression could be linked to other systems (Bryan)
- If select box only has one valid option it will be selected automatically (Bryan)
- Popups to choose associated issues now has option to choose issue by ID (Bryan)
- Added a prompt message to confirm the closing of the email window (Bryan)
- Changed reminder system to allow date fields to be compared with other date fields (Bryan)
- Changed IRC notification system to not always be tied to a specific issue (Bryan)
- Workflow: When a new email is received, the handleNewEmail method is always called. Previously the method was only called if the email was associated with an issue (Bryan)
- Fixed bug that generated error when changing priority (Bryan)
- Added option to list custom fields on list issues page (Bryan)
- Fixed 'Issues by Release' link to actually only list issues from the correct release (Bryan)
- Added option to force reminders to not count weekends when performing date calculations (Bryan)
- Made certain popups resizable (Bryan)
- Fixed bug with outdated information being emailed to a user when a new project is assigned to them (Bryan)
- If customer integration is not enabled, don't mention customers in warning message (Bryan)
- Workflow: Added workflow method to restrict what statuses can be set for a specific issue (Bryan)
- Updated PEAR Net_UserAgent_Detect class to be compatible with PHP5 (Bryan)
- Added ability to dynamically control which columns are displayed on issue listing screen (Bryan)
- Added the 'Release' field to the advanced search page (Bryan)
- Added email configuration form to installation screen (Bryan)
- Fixed the custom field code to properly display the current value of a textarea-field when trying to update their information (João)
- Updated the custom field handling code in the anonymous report form (João)
- Automatically redirect to the second step of the anonymous report form if there is just one project to select (João)
- Added code to respect the allow-unassigned-issues feature in the anonymous report form (João)
- Rewrote the CVS integration script in PHP (João)
- Moved the file upload form to a popup window (João)
- Changed the template code to allow popup windows to be resized (João)
- Fixed the installation screen to properly display a warning if the IMAP extension is not enabled (João)
- Added some documentation about some of the management screens (João)
- Moved the log files into a separate directory (João)
- Rewrote the locking mechanism for most of the interactive scripts (João)
- Fixed the phone entry window to automatically close after submitting the form (João)
- Added the ability to create custom filters for "un-assigned" issues and issues assigned to "myself or un-assigned" (João)
- Fixed bug that was causing a SQL error when deleting projects if no email accounts were associated with it (Bryan)
- Added 'Recent Activity Report' to show phone calls added recently (Bryan)
- Fixed bug with phone call entry not defaulting to current local time (Bryan)
- Fixed bug with reminder system where expired contracts were not being excluded (Bryan)
- Added option to search by events in past X hours on advanced search page (Bryan)
- Added support for multiple incident types (Bryan)
- Following a direct link to an issue will no longer prompt for a project when you login (Bryan)
- Automatically switch projects if viewing an issue in a project other then the currently selected one (Bryan)
- Added default charset of 'ISO-8859-1' (Bryan)
- Inline file attachments (such as images) now have filename set (Bryan)

## 1.3.1 - 2004-09-15

- Added the missing maq_iss_id and maq_subject columns to the mail_queue table schema (João)
- Fixed a database schema upgrade bug that tried to do "default '0'" in a auto_increment field (João)
- Added a missing named anchor in the example backend customer information template (João)
- Fixed the create issue form priority drop-down box to default to 'Please choose a priority' (João)
- Added the support for a non-standard MySQL port (João)
- Changed the issue reply window to have a unique name so one can reply to multiple issues at once (João)
- Fixed the FAQ and News modules to automatically activate links when displaying the content (João)
- Added the ability to sort by the Last Action Date column in saved searches (João)
- Added an initial set of commands to the IRC bot: !help; !auth; !clock; !list-clocked-in and !list-quarantined (João)

## 1.3 - 2004-09-10

- Customer integration API (João, Bryan)
- Custom Workflow API (Bryan)
- Made canned email responses be customizable in a per-project basis (João)
- Made priorities be customizable in a per-project basis (João)
- Fixed the SQL schema to avoid the mystic "Invalid default value for 'sta_id'" error message when installing the application (João)
- Automatically add a slash in the end of the installation path to avoid configuration problems (João)
- Fixed bug in custom field report when not graphing all options (Bryan)
- Forced order of graph bars on custom field report to match order options are listed in the select box (Bryan)
- Added customer stats report (Bryan)
- Added ability to specify which fields should be displayed on the issue creation form (Bryan)
- Fixed the "Forgot My Password" code to check for null results (Clay Loveless)
- Improved the form validation of the email account form (Clay Loveless)
- Fixed a bug that was preventing history entries to be created about an assignment from an issue automatically created from an email (Clay Loveless, João)
- Added support for "date field is NULL" type search (João)
- Added the ability to create global custom filters (João)
- Displaying the history of reminder actions triggered for a specific issue (João)
- Added time tracking shortcuts to the note and draft popups (João)
- Added a feature to display the assignment information for an issue when doing IRC notifications (João)
- Added a new option to silently associate an email with an existing issue (João)
- Clear out any email listing screen search parameters when switching the currently selected project (João)
- Added shortcuts for the list-files and get-file CLI commands (João)
- Added a feature to include the backtrace of an error if we have access to the debug_backtrace() function (João)
- Added note to remind people to protect their setup directories after installation (João)
- Removed the "default notification options" feature. Too complex for something that should be simple (João)
- Added user preference to automatically pre-fill the email signature in the internal notes module (João)
- Fixed a bug that would continualy add `[#3333] Note: ` to messages that already have that in the subject line (João)
- Added a feature to save the issue/email listing screen search parameters in a per-project basis, so switching projects no longer is a problem (João)
- Added a feature to allow a issue reminder to trigger a specific action and also a IRC notification (João)
- Fixed the issue-xxxx@ code to properly ignore vacation auto-responder messages (João)
- Improved the Mail_Queue class code to remove any Reply-To: values prior to sending the messages out (João)
- Added some performance tweaks to a few screens (João)
- Added a feature to hide issue resolution from interface if no resolutions are set (Bryan)
- Changed CLI 'open-issues' command to use a case insensitive search (Bryan)
- Added email notification for when issue assignment is changed (Bryan)
- Added Group support (Bryan)
- Display who performed the action when sending notification messages (Bryan)
- Changed titled of draft window to 'Create Draft' (Bryan)
- Added option to hide fields from users of a specific role on the create issue form (Bryan)
- Added ability for issues to be 'Quarantined' when they are created (Bryan)
- Added sorting on 'Last Action Date' column (Bryan, João)
- Changed the history of changes window to dynamically hide internal-only actions from customer users (Bryan)
- Fixed bug in the notification email code to properly display the project name in the outgoing emails (Clay Loveless)

## 1.2.2 - 2004-06-30

- Forced cookies to always be set using APP_RELATIVE_URL to prevent multiple cookies from being created (Bryan)
- Properly handling email attachments with uppercase MIME related values (João)
- Fixed the email and note routing scripts to parse MIME emails and fetch the appropriate message body (João)
- When handling a routed note, add all email addresses from staff users from both To: and Cc: list to notification list (João)
- Properly handle quoted-printable message bodies (João)
- Weekly report can now be generated for any time period (Bryan)
- Added new Custom Field Report (Bryan)
- Download emails script no longer requires a mailbox name if using a pop3 account (Bryan)
- Fixed bug where values for new custom fields could not be set on existing issues (Bryan)
- When replying to an email from the web interface, set the In-Reply-To: header accordingly (João)
- Added an automatic check to handle concurrency issues with the mail queue process script (João)
- Phone call module now uses expandable cell to save space (João; Bryan)
- When Phone call is added, time tracking entry is also added (João; Bryan)
- Moved description to separate table to prevent wide descriptions from pushing the rest of the table over (Bryan)
- Added security to reports to prevent users with a role lower than "Standard User" from accessing them (Bryan)
- Auto reconnect to the IRC server if the connection is lost (João)
- Fixed cookie related problem that prevented users from logging into IIS based installations (João)
- Fixed a bug that was preventing the selected list of statuses from being stored when creating a new project (João)

## 1.2.1 - 2004-06-15

- Fixed the email download routine to properly handle emails without any issue association (João)
- Changed the reminder email alert so it displays the current assignment list (João)
- Fixed the database upgrade script to properly respect the table prefix chosen by the user (João)

## 1.2 - 2004-06-14

- SMS email address can now be set back to empty (Bryan)
- Fixed a problem with a duplicate key name in the history_type table (João)
- Fixed a few database migration problems when upgrading from an old snapshot release (João)
- Added a missing directory required in order for the IRC bot to work (João)
- Added a feature to auto-create issues from downloaded emails (João)
- Reworked the code that handles the automatic association of email into issues (João)
- Added a missing directory required in order for the diff-style issue updated notification emails to work (João)
- Added a usr_id field to the email table to make reporting on that table easier (Bryan)
- Separated the body and full email fields from the email table into a separate one to improve query performance (João)
- Added a missing reference to the 'noted_emails|notes' directories in the INSTALL file (João)
- Removed 'to' field when sending emails from an issue since emails are sent to notification list (Bryan)
- Fixed a bug in which the selected date for a phone call would be ignored (João)

## 1.1 - 2004-06-05

- Initial release (João; Bryan)
