<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

$autoload = null;
foreach ([__DIR__ . '/vendor/autoload.php', __DIR__ . '/../../../vendor/autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        break;
    }
}

if (!file_exists($autoload)) {
    echo <<<EOF

    You must set up the project dependencies, run the following commands:

    $ curl -sS https://getcomposer.org/installer | php
    $ php composer.phar install

EOF;
    exit(1);
}

require $autoload;
