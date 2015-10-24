### Using the IRC bot

With Eventum you can have an IRCbot if your development team has an IRC channel. The bot's features include, but are not limited to...

-   Authenticating users
-   Clocking in and out
-   Reporting when new issues are created
-   Reporting when issues are updated
-   Listing issues that are relevant to each user

Setup
-----

To get the bot working, first you have to go to /path-to-eventum/misc/irc/bot.php and edit a few values. In Eventum 1.6.1, these are on line 43-55

    $channels = array(
        Project::getID('Default Project') => array(
            '#issues',
        )
    );
    $irc_server_hostname = 'localhost';
    $irc_server_port = 6667;
    $nickname = 'EventumBOT';
    $realname = 'Eventum Issue Tracking System';
    // do you need a username/password to connect to this server? if
    // so, fill in the next two variables
    $username = '';
    $password = '';

\$channels : This array lists what channels belong to what projects, so the bot can be of use to several diffrent projects, as long as they are on the same network. You can also have more than one channel per project, like this:

<!-- -->

    $channels = array(
        Project::getID('Default Project') => array(
            '#issues','#myIssues ThisIsMyPassword'
        ),
        Project::getID('My Second Project') => array(
            '#moreissues'
        )
    );

\$irc_server_hostname : This variable holds the hostname or IP of the IRC server. Examples are 'irc.freenode.net' or 'localhost' if you use a private one hosted on the same server as Eventum.
\$irc_server_port : The port number for the IRC server. Default is 6667.
\$nickname : The nickname you want the bot to use. This has to be unique.
\$realname : What the bot will identify itself as if anyone executes a WHOIS command on it.
\$username : You MUST fill in this variable, even if the server is not restricted. Anything goes here, unless you have been given a username/password by the server administrator.
\$password : Same as for \$username.

When done, it might look something like this...

    $channels = array(
        Project::getID('TvT2') => array(
            '#tvt-dev aPassword',
        )
    );
    $irc_server_hostname = 'irc.tvt.mine.nu';
    $irc_server_port = 6667;
    $nickname = 'Eventum';
    $realname = 'Eventum Issue Tracking System';
    // do you need a username/password to connect to this server? if
    // so, fill in the next two variables
    $username = 'identd';
    $password = 'random';

To start the bot, execute "php -q bot.php" in the /path-to-eventum/misc/irc/ directory.