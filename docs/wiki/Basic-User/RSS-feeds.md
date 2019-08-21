### RSS feeds

Eventum has a feature to provide RSS feeds of custom filters, which is basically a way to save advanced search parameters into a special URL that you can call out to check on results.

If you click on the advanced Search link and save a search, this named search will appear on the bottom of the web page with an RSS button. If you copy the link that you get from the button, you may use that link to set up your rss reader or use a browser such as firefox which has an rss reader built in.

RSS links look like this: `https://eventum.example.org/rss.php?custom_id=12345`

To create a RSS feed:

-   Go to Advanced Search Page.
-   Customize your search within the form options.
-   Press the Save Search button, a popup windows appears indicating the custom filter was saved successfully.
-   The saved searh is listed in Saved Searches, under the Advanced Search Form, with a RSS icon next to each entry, linked to the feed.
-   When using the feed link, basic authentication is required (popup window asking for user and password).
-   Enter the same login and password used to log into Eventum.

If you have already logged in once during the session, It will access the feed, but if you try to access the rss.php file without authentication or from outside Eventum, you will be prompted for the login information. Notice this is a [Basic Authentication](http://en.wikipedia.org/wiki/Basic_access_authentication) (like htaccess) which differs from Eventum authentication; you should use the same Eventum user account login and password, with the one you created the search.

![Image:Eventum-RSS_feeds.png](Eventum-RSS feeds.png "Image:Eventum-RSS_feeds.png")

Notice you have to provide an Eventum login for the RSS feed to work. Authenticated session is not required to access the RSS feed.

Notice the the provided custom filter ID must be associated with the given email address (basic authentication data).

## Why is additional authentication required?

Since we can't simply have an open window into a potential confidential database of issues/bugs/tickets, the RSS feed script authenticates the user with HTTP Auth. This kind of authentication is necessary instead of a session in the browser, so any [RSS client](http://en.wikipedia.org/wiki/RSS_Reader) (not necessarily web-based) can use it without logging into Eventum as a web application.

Another way is bookmarking with credential in url, so eg. with our example will be: `https://username:password@eventum.example.org/rss.php?custom_id=12345`

## After entering correct login and password in the window, it pops again

When you enter user and password as required, the windows keeps appearing empty once and again, and if you cancel, the message "Error: You are required to authenticate in order to access the requested RSS feed." is displayed. This type of authentication might not work if you are using PHP with [FastCGI](http://www.fastcgi.com).
