### Setting up fulltext searching

As of version 1.5.4 Eventum supports full-text searching of issue summary, description, notes, emails, phone calls and time tracking entries. By default this is turned on.

To enable this, edit /path-to-eventum/config/config.php and change the line:

`@define("APP_ENABLE_FULLTEXT", false);`

to:

`@define("APP_ENABLE_FULLTEXT", true);`

For information on full-text search syntax, or other full text information, please visit the following link.

<http://dev.mysql.com/doc/mysql/en/fulltext-boolean.html>

Please note the following when using full-text searching.

1. MySQL, by default, only stores words that are 4 chars or longer. If you need to search for smaller words, then you will need to update your MySQL configuration.

2. When searching for multiple words, full text returns an entry if ANY SINGLE entry is located. If you enter the following search "foo bar" then all issues with the word foo, all issues with the word bar, and all issues that have both foo and bar will be returned. If you want only all options that have BOTH words in them, then you may alter your search to " foo bar".

3. By default, there is a built in list of words that MySQL does not include in it's full-text index. This list can be found at <http://dev.mysql.com/doc/mysql/en/fulltext-stopwords.html> If you do need to include any of these words, you will need to update your MySQL configuration.
