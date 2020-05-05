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

namespace Eventum\ServiceProvider;

use Eventum\Config\SphinxConfig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SphinxClient;

class FulltextSearchService implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app[SphinxConfig::class] = static function ($app) {
            return new SphinxConfig($app['config']);
        };

        $app[SphinxClient::class] = static function ($app) {
            /** @var SphinxConfig $config */
            $config = $app[SphinxConfig::class];

            $sphinx = new SphinxClient();
            $sphinx->SetServer($config->host, $config->port);

            return $sphinx;
        };
    }
}
