#!/bin/sh
set -xeu

PHP_INI_DIR=~/.phpenv/versions/$(phpenv version-name)/etc
install -d $PHP_INI_DIR

# disabled: broken on trusty (and we have tests disabled)
#echo "extension=ldap.so" >> $PHP_INI_DIR/php.ini
composer config platform.ext-ldap '0'

# no gd for php 8.0
# https://travis-ci.org/glensc/eventum/jobs/490544010
composer config platform.ext-gd '0'

# add mongodb extension
# https://github.com/eventum/eventum/pull/519
MONGODB_VERSION=1.5.0
# don't really need it being present, so fake
#pecl install -f mongodb-$MONGODB_VERSION
#composer config platform.ext-mongodb $MONGODB_VERSION

# disable xdebug
phpenv config-rm xdebug.ini || :

# PHP 7.2 does not have mcrypt
# Installation request for defuse/php-encryption ~1.2.1 -> satisfiable by defuse/php-encryption[v1.2.1].
composer config platform.ext-mcrypt '0'

# disable secure-http because sourceforge redirects to http:// urls
composer config secure-http false
