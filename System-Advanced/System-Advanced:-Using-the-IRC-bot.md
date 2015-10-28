### Using the IRC bot

The IRC notification bot is a nice feature for remote teams that want to handle issues and want to have a quick and easy way to get simple notifications. The bot currently notifies of the following actions:

-   New Issues
-   Blocked emails
-   Issues with assignment list changes

With Eventum you can have an IRC bot if your development team has an IRC channel. The bot's features include, but are not limited to...

-   Authenticating users
-   Clocking in and out
-   Reporting when new issues are created
-   Reporting when issues are updated
-   Listing issues that are relevant to each user

The bot also provides a simple set of commands which can be invoked in a query to the bot user:

```
<user> help
<EventumBOT> This is the list of available commands:
<EventumBOT> auth: Format is "auth user@example.com password"
<EventumBOT> clock: Format is "clock [in|out]"
<EventumBOT> list-clocked-in: Format is "list-clocked-in"
<EventumBOT> list-quarantined: Format is "list-quarantined"
```

Setup
-----

You will need to provide a `config/irc_config.php` file with appropriate preferences, such as the IRC server and channel that the bot should join. An example setup file can be found in [config/irc_config.dist.php](https://github.com/eventum/eventum/blob/master/config/irc_config.dist.php)

- `channels` : This array lists what channels belong to what projects, so the bot can be of use to several different projects, as long as they are on the same IRC network. You can also have more than one channel per project, like this:
```php
    'channels' => array(
        'Default Project' => array(
            '#issues', '#myIssues ThisIsMyPassword'
        ),
        'My Second Project' => '#moreissues',
    ),
```
- `'hostname'` : This variable holds the hostname or IP of the IRC server. Examples are `irc.freenode.net` or `localhost` if you use a private one hosted on the same server as Eventum.
- `'port'` : The port number for the IRC server. Default is 6667.
- `'nickname'` : The nickname you want the bot to use. This has to be unique.
- `'realname'` : What the bot will identify itself as if anyone executes a WHOIS command on it.
- `'username'` : You MUST fill in this variable, even if the server is not restricted. Anything goes here, unless you have been given a username/password by the server administrator.
- `'password'` : Same as for `'username'`.

When done, it might look something like this...

```php
return array(
    /// connection parameters
    // IRC server address
    'hostname' => 'irc.tvt.mine.nu',
    'port' => 6667,
    'nickname' => 'EventumBOT',
    'realname' => 'Eventum Issue Tracking System',

    // do you need a username/password to connect to this server?
    // if so, fill in the next two variables
    'username' => 'identd',
    'password' => 'random',

    // configured IRC channels
    'channels' => array(
        'TvT2' => array(
            '#tvt-dev aPassword',
        ),
    ),
);
```

To invoke the notification bot and let it run on the server, run this command:

    bin/irc-bot.php &

It is recommended to enable [pcntl](http://php.net/pcntl) extension to handle `Ctrl`+`C` and `SIGTERM` gracefully (automatically cleaning up pid file).