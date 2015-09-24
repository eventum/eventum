# Localization

The localization of Eventum into other languages is currently handled through the [Launchpad Project Page](https://launchpad.net/eventum/).  

**We need your help!**  
Please consider contributing to the translation effort.

## Translation Status  

You can find a list of the available languages and status, please see the [Translation Status page](https://translations.launchpad.net/eventum/trunk/+pots/eventum).  

## Imported from Launchpad

The [localization files](https://github.com/eventum/eventum/tree/master/localization) shown in GitHub appear to be out-of-date, but don't worry.  The localization files are imported from Launchpad for every release, see the [release script](https://github.com/eventum/eventum/blob/master/bin/release.sh#L50-L70) for additional details.

## How Localization Works

Eventum uses the [smarty-gettext](https://github.com/smarty-gettext/smarty-gettext) package to handle localization.  
Strings that require translation can be found in the HTML templates or PHP.

* HTML templates files will surround the text to be translated with '`{t}`' and '`{/t}`'.  
`<html> <body> {t}Hello world{/t} </body> </html>`  
* Strings found in the PHP files use the `gettext('_string_')` function.  
`<?php echo gettext('Hello world'); ?>`  

This localization files contain name value pairs for each translation item.  The original string is identified with `msgid` and the `msgstr` value contains the localized string.

`msgid "Hello world"`  
`msgstr "Hola mundo"`

> Spanish Output: Hola mundo

## Adding a new language to Eventum

To add a language not shown in the [[translation status page|https://translations.launchpad.net/eventum/trunk/+pots/eventum]], please review the [[older documentation|System-Advanced:-Localization:-Old-Style]] covering gettext, Poedit, and how localization is added to Eventum.