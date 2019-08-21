### Fulltext Search

Fulltext search allows you to search issues by using [MySQL FULLTEXT support](http://dev.mysql.com/doc/refman/5.0/en/fulltext-search.html)

## User Hints

Fulltext indexing in Support:

The Eventum System maintains Fulltext indexes for the relevant columns in these tables:

-   issue
-   note
-   time tracking
-   phone support
-   support email body
-   issue custom field

Use +foo+bar when you want to search 'AND'
When searching for multiple words, full text returns an entry if ANY SINGLE entry is located. If you enter the following search "foo bar" then all issues with the word foo, all issues with the word bar, and all issues that have both foo and bar will be returned. If you want only all options that have BOTH words in them, then you may alter your search to "+foo +bar".

Use -foo when you want to use 'NOT'
for example, try using this string in the support site +Enable +Full +Text -XML +TLA

Use parentheses to group expressions
for example, try this (+Enable +Full +Text -XML) +TLA

This variant now means 'any record that matches the expression in the parenthese', AND any record that matches 'TLA'

'Stop words' will be ignored
By default, there is a built-in list of words that MySQL does not include in it's full-text index. This list can be found at <http://dev.mysql.com/doc/mysql/en/fulltext-stopwords.html>

Learn more about doing Full Text searches at <http://dev.mysql.com/tech-resources/articles/full-text-revealed.html>

## Reference Docs

### MySQL

The documentation is at <http://dev.mysql.com/doc/refman/5.0/en/fulltext-search.html> <http://dev.mysql.com/doc/refman/5.0/en/mysql-indexes.html> (note that is for MySQL 5.0)

And for the 4.1 manual see <http://dev.mysql.com/doc/refman/4.1/en/fulltext-search.html>

### Eventum

[Setting_up_fulltext_searching](Setting-up-fulltext-searching.md) explains that there is a configuration switch to enable fulltext searching in the Eventum system.

## Code and Configuration

### Eventum

Set the APP_ENABLE_FULLTEXT flag in config.inc.php:

`// if full text searching is enabled`
`@define("APP_ENABLE_FULLTEXT", true);`

Also, the fulltext searching within Eventum is coded in includes/class.issue.php

Fulltext indexes are maintained for the issue, note, time_tracking, phone_support, support_email_body, issue_custom_field tables.

see the section about MySQL configuration for how to rebuild the indexes

### MySQL Database Server

Configure MySQL to include two-letter or three-letter words in search indexing:

(from <http://www.mysql.com/doc/en/Fulltext_Fine-tuning.html>) The minimum length of words to be indexed is defined by the MySQL variable ft_min_word_len . See section 4.6.8.4 SHOW VARIABLES. (This variable is only available from MySQL version 4.0.) The default value is four characters. Change it to the value you prefer, and rebuild your FULLTEXT indexes. For example, if you want three-character words to be searchable, you can set this variable by putting the following lines in an option file: `[mysqld] ft_min_word_len=3` Then restart the server and rebuild your FULLTEXT indexes.

### Rebuilding MySQL Fulltext Indexes

#### REPAIR TABLE QUICK

The fastest way to rebuild the indexes is to issue the following SQL statements

`REPAIR TABLE issue QUICK;`
`REPAIR TABLE note QUICK;`
`REPAIR TABLE time_tracking QUICK;`
`REPAIR TABLE phone_support QUICK;`
`REPAIR TABLE support_email_body QUICK;`
`REPAIR TABLE issue_custom_field QUICK;`

Note that the DROP/ADD procedure described below should be used if you have LARGE datasets, as it is much more efficient to drop the index and recreate it. With large (million+) datasets, it may take much longer to repair an index v. drop and add it.

#### DROP/ADD

Using phpMyAdmin, it is rather easy to update (rebuild) the indexes of a single table using point and click. Let's use the note table as an example. Looking at the structure of the table, it describes the existing indexes. If you click on 'edit' for the FULLTEXT index, and do not change anything about the definition of the index; then click on the 'Go' button, you will have effectively run this SQL statement

```sql
 ALTER TABLE `note` DROP INDEX `ft_note` ,
   ADD FULLTEXT `ft_note` (`not_title`,  `not_note`)
```

Note that changing the ft_min_word_len value in the mysqld section of my.cnf must also be done in the myisamchk section, or on the commandline if you are using the myisamchk tool because this configuration is not actually stored within the .MYI files!!!

from <http://dev.mysql.com/doc/refman/4.1/en/fulltext-fine-tuning.html>:

> Note that if you use myisamchk to perform an operation that modifies table indexes (such as repair or analyze), the FULLTEXT indexes are rebuilt using the default full-text parameter values for minimum word length, maximum word length, and stopword file unless you specify otherwise. This can result in queries failing.
>
> The problem occurs because these parameters are known only by the server. They are not stored in MyISAM index files. To avoid the problem if you have modified the minimum or maximum word length or stopword file values used by the server, specify the same ft_min_word_len, ft_max_word_len, and ft_stopword_file values to myisamchk that you use for mysqld. For example, if you have set the minimum word length to 3, you can repair a table with myisamchk like this:

`shell> myisamchk --recover --ft_min_word_len=3 tbl_name.MYI`

> To ensure that myisamchk and the server use the same values for full-text parameters, place each one in both the [mysqld] and [myisamchk] sections of an option file:

```
[mysqld]
ft_min_word_len=3

[myisamchk]
ft_min_word_len=3
```

> An alternative to using myisamchk is to use the REPAIR TABLE, ANALYZE TABLE, OPTIMIZE TABLE, or ALTER TABLE statements. These statements are performed by the server, which knows the proper full-text parameter values to use.

## Other Resources on Fulltext Searching

Tim Bray on search:

Tim Bray has released a series of essays all about full-text searching… looks good and probably worth a read. It begins with explaining the concepts, touches on Internationalization issues, and touches on some more advanced topics like Result Ranking. The way it is laid out, you can easily cherry pick the sections relevant to you.

<http://www.tbray.org/ongoing/When/200x/2003/07/30/OnSearchTOC>

schema.sql
You may look at schema.sql to see which tables have FULLTEXT indexes.
