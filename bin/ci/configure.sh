#!/bin/sh
set -xe

PHP_INI_DIR=~/.phpenv/versions/$(phpenv version-name)/etc
install -d $PHP_INI_DIR

# disabled: broken on trusty (and we have tests disabled)
#echo "extension=ldap.so" >> $PHP_INI_DIR/php.ini
composer config platform.ext-ldap '0'

# no gd for php 8.0
# https://travis-ci.org/glensc/eventum/jobs/490544010
composer config platform.ext-gd '0'

# disable xdebug
phpenv config-rm xdebug.ini || :

# PHP 7.2 does not have mcrypt
# Installation request for defuse/php-encryption ~1.2.1 -> satisfiable by defuse/php-encryption[v1.2.1].
composer config platform.ext-mcrypt '0'

# disable secure-http because sourceforge redirects to http:// urls
composer config secure-http false

# install as global, because via composer autoloading is broken
# due the way _setlocal gets defined based on context
# $ vendor/bin/phpunit
# PHP Fatal error:  Uncaught Error: Call to undefined function _setlocale() in /home/travis/build/glensc/eventum/lib/eventum/class.language.php:158
composer global require "phpunit/phpunit" "^7.5 || ^8.0"
