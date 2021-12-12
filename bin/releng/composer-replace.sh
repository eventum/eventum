#!/bin/sh
set -eu

# setup "replace" section in composer.json
replace=$(jq .extra.replace composer.json)
composer=$(mktemp composerXXXXXX)
jq --arg replace "$replace" '.replace = ($replace|fromjson)' composer.json > $composer
mv -f $composer composer.json

# now remove the packages
packages=$(jq -r '.extra.replace|keys[]' composer.json)
set -x
composer remove $packages --update-no-dev --no-scripts --no-update-with-dependencies --ansi
