#!/bin/sh
set -xeu

PHP_INI_DIR=~/.phpenv/versions/$(phpenv version-name)/etc
install -d $PHP_INI_DIR

# disabled: broken on trusty (and we have tests disabled)
#echo "extension=ldap.so" >> $PHP_INI_DIR/php.ini
composer config platform.ext-ldap '0'

# no gd for php 8.0
composer config platform.ext-gd '0'

# disable xdebug
phpenv config-rm xdebug.ini || :

# disable secure-http because sourceforge redirects to http:// urls
composer config secure-http false
