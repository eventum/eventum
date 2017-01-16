#!/bin/sh
set -xe

PHP_INI_DIR=~/.phpenv/versions/$(phpenv version-name)/etc
install -d $PHP_INI_DIR

# enable ldap ext
echo "extension=ldap.so" >> $PHP_INI_DIR/php.ini

# disable xdebug
phpenv config-rm xdebug.ini ||
