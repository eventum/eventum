# Localization

## smarty-gettext

Eventum uses the [smarty-gettext](https://github.com/smarty-gettext/smarty-gettext) package to handle localization.   

* HTML templates files will surround the text to be translated with '`{t}`' and '`{/t}`'.  
`<html> <body> {t}Hello world{/t} </body> </html>`  
* Strings found in the PHP files use the `gettext('_string_')` function.  
`<?php echo gettext('Hello world'); ?>`  

> Spanish Output: Hola mundo

This localization files contain name value pairs for each translation item.  The original string is identified with `msgid` and the `msgstr` value contains the localized string.

`msgid "Hello world"`  
`msgstr "Hola mundo"`

When you localize the file, you will put the localized version of the string into the msgstr.

The localization of Eventum into other languages is currently handled through the [Launchpad Project Page](https://launchpad.net/eventum/).  

## Translation Status  

You can find a list of the available languages and status, please see the [[Translation Status page|https://translations.launchpad.net/eventum/trunk/+pots/eventum]].  

## Imported from Launchpad

The [localization files](https://github.com/eventum/eventum/tree/master/localization) shown in GitHub appear to be out-of-date, but don't worry, localization files are imported from Launchpad for each release.  See the [release script](https://github.com/eventum/eventum/blob/master/bin/release.sh#L50-L70) for additional details.

[[PO Edit|System-Advanced:-Localization:-Old-Style]]