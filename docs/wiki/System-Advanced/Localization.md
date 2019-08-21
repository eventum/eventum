# Localization

**We need your help!**  
Please consider contributing to the translation effort.

The localization of Eventum into other languages is currently handled through the [Launchpad Project Page](https://launchpad.net/eventum/).

## Translation Status

You can find a list of the available languages and status, please see the [Translation Status page](https://translations.launchpad.net/eventum/trunk/+pots/eventum).

## Imported from Launchpad

The localization files are imported from Launchpad for every release, see the [release script](https://github.com/eventum/eventum/blob/v3.0.2/bin/release.sh#L59-L70) for additional details.

## How Localization Works

Eventum uses the [smarty-gettext](https://github.com/smarty-gettext/smarty-gettext) package to handle localization.  
Strings that require translation can be found in the HTML templates or PHP.

-   HTML templates files will surround the text to be translated with '`{t}`' and '`{/t}`'.  
    `<html> <body> {t}Hello world{/t} </body> </html>`
-   Strings found in the PHP files use the `ev_gettext('_string_')` function.  
    `<?php echo ev_gettext('Hello world'); ?>`

This localization files contain name value pairs for each translation item. The original string is identified with `msgid` and the `msgstr` value contains the localized string.

`msgid "Hello world"`  
`msgstr "Hola mundo"`

> Spanish Output: Hola mundo

### Translation Notes

https://github.com/eventum/eventum/pull/222/files#r90018478

```php
// TRANSLATORS: %1 - issue_id, %2: issue summary
$full_subject = ev_gettext('[#%1$s] New Issue Assigned: %2$s' ,$issue_id, $data['iss_summary']);
```

### System Variables & Example Strings

When making contributions to the localization effort, there are system variables and example strings that do not require translation.

`%1$s`  
`%2$s`  
`@example.com`

## Adding a new language to Eventum

To add a language not shown in the [translation status page](https://translations.launchpad.net/eventum/trunk/+pots/eventum), please review the [older documentation](../System-Advanced/Localization-Old-Style.md) covering gettext, Poedit, and how localization is added to Eventum.

The information covered in the older documentation, covers how to create localization files for your own language. Once the files have been created, they can be included in Eventum by [reporting a bug](https://bugs.launchpad.net/eventum/+filebug) on the LaunchPad project page.
