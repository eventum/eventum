## Localization ##

This is the project page for localizing Eventum into different languages.

In order to have multilanguage support you have to take the following steps:

Enabling Localization
---------------------

To actually use a language in eventum your server has to support that locale. For debian based systems it is as simple as running:

`(sudo) dpkg-reconfigure locales`

Please check OS documentation for other operating systems.

How to Localize
---------------

We will be using the excellent [smarty-gettext](http://smarty.incutio.com/?page=SmartyGettext) package. This means that templates files will need to be modified to surround text we want to translate with '{t}' and '{/t}'. For text strings found in PHP files, simply wrap them with gettext('string');

Once all the templates are complete, eventum.po files will be generated. This file will contain name value pairs like:

`msgid "Issue"`
`msgstr ""`

When you localize the file, you will put the localized version of the string into the msgstr.

Adding a new language to Eventum (2.2)
--------------------------------------

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

-   make sure you name your .po file: *nl.po* or what ever 2-lettercode for your language
-   if you generate the .mo file yourself, for instance with poEdit, make sure it is called *eventum.po*

If all goes well and your gettext is properly installed, you can test to see if localization is active by:

-   Log in to eventum
-   Go to the user preference screen and check if your newly added language is present in the *Available Languages* dropdownbox.

### Localization Notes

Do not localize `@example.com` addresses, they are supposed to stay that way, see: <http://example.com/>

### Programs

Programs that make the job much easier.

Standalone

-   [KBabel](http://kbabel.kde.org/) (Linux)
-   [poEdit](http://www.poedit.net/) (Linux & Windows)

Web-based

-   [Pootle](http://translate.sourceforge.net/) (Python), e.g [WordForge](http://pootle.wordforge.org/)
-   [Webabel](http://kazit.berlios.de/webabel/) (PHP) - **A Challenge to Get Working & No Documentation**
-   [Kartouche](http://www.dotmon.com/kartouche/) (PHP), e.g [<http://www.kyfieithu.co.uk/index.php?lg=en>& Kyfieithu] - **A Challenge to Get Working - Strange Includes**
-   [Rosetta](https://translations.launchpad.net/)(?)
    -   "...Rosetta is not Open or Free Software at the moment. Rosetta will become open source sometime in the future but we don't have a date, although some parts of the Launchpad have already been released under the GPL by Canonical Ltd." Source: [Rosetta FAQ](https://help.launchpad.net/RosettaFAQ)

-   [Pootle Installed](http://translate.unixlan.com.ar/projects/eventum/) Pootle is installed and available for translating languages which already have some advance. Mailing List archive available [HERE](http://www.unixlan.com.ar/list/).

Planned Languages
-----------------

We are planning on translating Eventum into the following languages. If you would like to help with a translation, please add your name under your language. If you do not see your language on the list, and would like to volunteer to help translate it, please add it to the list.

-   Chinese Traditional

`  Chiu Cheng Chung `<morris32(at)ms26(dot)hinet(dot)net>

-   Chinese

`  Zhang Shengjun `<www.scu.edu(at)163(dot)com>

-   Czech

`  Jan Horak `<horak(at)teraton(dot)cz>

-   Dutch

`  Erwin Wondergem `<e.wondergem@zeeland.nl>
`  Milo van der Linden `<m.vanderlinden@avd-ict.nl>

-   Finnish [Done]

`  Jyrki Heinonen `<jyrki.heinonen@gmail[d.o.t]com>

-   French

`  No more supported.`

-   German

`  Ralf Kuehnbaum-Grashorn `<ralf@kuehnbaum.de>
`  Wolfgang Gassler `<w.gassler(at)medienhof(dot)at>
`  `[`Bene` `Homann`](http://www.pragerplatz.de/)`, `[`bene@pragerplatz.de`](mailto:bene@pragerplatz.de)
`  You will find the german support page (de_DE, de_AT, de_CH) `[`here`](Localization:German "wikilink")`.`

-   Hungarian

`   Péter Sipka`
`   `[`sipkap@t-online.hu`](mailto:sipkap@t-online.hu)

-   Italian

`  Frank A.`

-   Norwegian

`  none, at the moment ..`

-   Polish

`  Grzegorz Sterniczuk `<grzegorz(at)tyski(dot)pl>
`  Piotr Lada `<piotr.lada(at)helimed(dot)pl>

-   Russian [Done]

`  Andrey Popovich `<andrey@popovich.kiev.ua>
`  Volik Eugeniy `<evolik(at)gmail(dot)com>

-   Spanish - for 2.1.1 download [here](http://translate.unixlan.com.ar/es/), instructions [<http://www.unixlan.com.ar/list/?0>::54 here]

`  Edwin Cruz `<ecruz @t cuboit d0t com>` `
`  Isaac López `<rilsoft@gmail.com>
`  Gustavo `<gusbeiro@montevideo.com.uy>
`  Germán Pizarro `<gpizarro1977[at]yahoo(d0t)com>
`  Normando Hall `<nhall[at]unixlan(dot)com(dot)ar>

-   Swedish [Done]

`  Jostein Martinsen `<jostein(dot)martinsen [at] redpill {dot} se>
`  Joakim Norlinder `<joakim.norlinder@redpill.se>
`  Johan Carlsson `<johan.carlsson@redpill.se>
`  Maria Oberg `<maria.oberg@redpill.se>
`  Fredrik Svensson `<fredrik.svensson@redpill.se>

-   Japanese (日本語) - Notes about [Japanese character sets](Localization:Japanese "wikilink") and E-mail integration.

`  Yukihiro Kawada `<yukihiro.kawada(at)gmail(dot)com>
`  Brian LaVallee `<brian.lavallee(at)pbxl(dot)com>

-   Slovak - for ver. 2.2 by email request

`  (sk_SK: 1019 translated messages, 844 untranslated messages) `
`  Milos Bielik `<milos.bielik {at) gmail. com>

Translation status
------------------

As of Eventum 2.1 Release translation status of the languages that have been submitted:

-   Dutch **OK**

` find the files `[`here`](http://www.zeeland.nl/assets/downloads/eventum_2.1.x_dutch.zip)

-   Finnish **OK**

` + msgfmt --statistics --output=/dev/null fi.po`
` 1781 translated messages, 1 untranslated message.`

-   Italian

` + msgfmt --statistics --output=/dev/null it.po`
` 1736 translated messages, 38 fuzzy translations, 8 untranslated messages.`

-   Polish

` + msgfmt --statistics --output=/dev/null pl.po`
` 583 translated messages, 28 fuzzy translations, 1168 untranslated messages.`

-   Russian

` + msgfmt --statistics --output=/dev/null ru.po`
` 1734 translated messages, 28 fuzzy translations, 6 untranslated messages.`

-   Swedish

` + msgfmt --statistics --output=/dev/null sv.po`
` 700 translated messages, 633 fuzzy translations, 449 untranslated messages.`

-   Spanish

` 100% translated, 1786 strings(13 fuzzy, 0 bad tokens, 0 not translated).`
` (not yet included in 2.1.1, download `[`here`](http://translate.unixlan.com.ar/es/)`, instructions [`[`http://www.unixlan.com.ar/list/?0`](http://www.unixlan.com.ar/list/?0)`::54 here]`

Preparing Templates
-------------------

Complete! See <Localization:Templates> for details on the process.

Translating PO Files
--------------------

[This tarball](http://glen.alkohol.ee/pld/eventum/eventum-r3471.tar.bz2) contains the latest development version of Eventum which contains PO files to be translated. The PO files will be located in /eventum/localization/<LANGUAGE>.po.

Once you have put the translated strings into the eventum file, run "msgfmt -f -o <LANGUAGE>/LC_MESSAGES/.mo <LANGUAGE>.po" to convert the PO file to a MO file, the binary format that gettext understands. If you have done this correctly, set APP_DEFAULT_LOCALE to your locale or change your language on the preferences page, you should see Eventum in your own language!

Known Issues
------------

[Untranslated Items](Localization:UntranslatedItems "wikilink")

External Reading Material
-------------------------

[A nice article by Joao Prado Maia on gettext](http://www.onlamp.com/pub/a/php/2002/06/13/php.html)