#!/bin/sh

# setup database
mysql -e 'CREATE DATABASE IF NOT EXISTS e_test'
cp -p tests/travis/*.php config
vendor/bin/phinx migrate -e test
vendor/bin/phinx seed:run -e test
