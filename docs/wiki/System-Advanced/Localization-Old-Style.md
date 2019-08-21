_The methods described described below are for older versions of Eventum, but the process still works with current versions._

_The localization of Eventum into other languages is currently handled through the [Launchpad Project Page](https://launchpad.net/eventum/)._

## Localization

This is the project page for localizing Eventum into different languages.

In order to have multilanguage support you have to take the following steps:

## Enabling Localization

To actually use a language in eventum your server has to support that locale. For debian based systems it is as simple as running:

`(sudo) dpkg-reconfigure locales`

Please check OS documentation for other operating systems.

## How to Localize

We will be using the excellent [smarty-gettext](http://smarty.incutio.com/?page=SmartyGettext) package. This means that templates files will need to be modified to surround text we want to translate with '{t}' and '{/t}'. For text strings found in PHP files, simply wrap them with gettext('string');

`{t}Hello{/t}`

Once all the templates are complete, eventum.po files will be generated. This file will contain name value pairs like:

`msgid "Hello"`
`msgstr "nuqneH"`

When you localize the file, you will put the localized version of the string into the msgstr.

## Adding a new language to Eventum (2.2)

Localization related files:

-   eventum/config/config.php -- Set the system wide default language
-   eventum/init.php -- Set the language for the active session
-   eventum/lib/eventum/class.language.php -- Initialize the language(s)
-   eventum/localization/\<2-letterCountryCode\>/LC_MESSAGES -- directory where the .po and .mo file need to be.

-   Step 1:

for a new language to be added to Eventum, you first of all need to edit the class.language.php. In this file there is a array with available languages:

    $avail_langs = array(
        'pl_PL' =>  'Polish',
    #    'en_US' =>  'English',
        'ru_RU' =>  'Russian',
    #    'de_DE' =>  'German',
    #    'fr_FR' =>  'French',
        'it_IT' =>  'Italian',
        'fi_FI' =>  'Finnish',
    #    'es_ES' =>  'Spanish',
        'nl_NL' =>  'Dutch',
        'sv_SE' =>  'Swedish',
    );

Make sure you uncomment the languages that you use, or add the code for the specific language you wish to install.

-   Step 2:

Next, make sure you create a directory for the .mo and .po files like:

-   eventum-2.1.x-rootdirectory/localization/nl/LC_MESSAGES

In this directory:

-   make sure you name your .po file: _nl.po_ or what ever 2-lettercode for your language
-   if you generate the .mo file yourself, for instance with poEdit, make sure it is called _eventum.po_

If all goes well and your gettext is properly installed, you can test to see if localization is active by:

-   Log in to eventum
-   Go to the user preference screen and check if your newly added language is present in the _Available Languages_ dropdownbox.

### Localization Notes

Do not localize `@example.com` addresses, they are supposed to stay that way, see: <http://example.com/>

### Programs

Programs that make the job much easier.

Standalone

-   <del>[KBabel](http://kbabel.kde.org/) (Linux)</del>
-   [poEdit](http://www.poedit.net/) (Linux & Windows)

Web-based

-   [Pootle](http://translate.sourceforge.net/) (Python), e.g <del>[WordForge](http://pootle.wordforge.org/)</del>
-   <del>[Webabel](http://kazit.berlios.de/webabel/)</del> (PHP) - **A Challenge to Get Working & No Documentation**
-   <del>[Kartouche](http://www.dotmon.com/kartouche/)</del> (PHP), e.g <del>[<http://www.kyfieithu.co.uk/index.php?lg=en>& Kyfieithu]</del> - **A Challenge to Get Working - Strange Includes**
-   [Rosetta](https://translations.launchpad.net/)(?)

    -   "...Rosetta is not Open or Free Software at the moment. Rosetta will become open source sometime in the future but we don't have a date, although some parts of the Launchpad have already been released under the GPL by Canonical Ltd." Source: [Rosetta FAQ](https://help.launchpad.net/RosettaFAQ)

-   <del>[Pootle Installed](http://translate.unixlan.com.ar/projects/eventum/)</del> Pootle is installed and available for translating languages which already have some advance. Mailing List archive available <del>[HERE](http://www.unixlan.com.ar/list/)</del>.

## Translating PO Files

[This tarball](http://glen.alkohol.ee/pld/eventum/eventum-r3471.tar.bz2) contains <del>the latest</del> `an old` development version of Eventum which contains PO files to be translated. The PO files will be located in /eventum/localization/<LANGUAGE>.po.

Once you have put the translated strings into the eventum file, run "msgfmt -f -o <LANGUAGE>/LC_MESSAGES/.mo <LANGUAGE>.po" to convert the PO file to a MO file, the binary format that gettext understands. If you have done this correctly, set APP_DEFAULT_LOCALE to your locale or change your language on the preferences page, you should see Eventum in your own language!

## Known Issues

[Untranslated Items](Localization:UntranslatedItems "wikilink")

## External Reading Material

[A nice article by Joao Prado Maia on gettext](http://www.onlamp.com/pub/a/php/2002/06/13/php.html)
