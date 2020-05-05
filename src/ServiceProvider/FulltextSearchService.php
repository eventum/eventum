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

use Abstract_Fulltext_Search;
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

        $app[Abstract_Fulltext_Search::class] = static function ($app) {
            $class = $app['config']['fulltext_search_class'];

            // legacy: handle lowercased classname
            if ($class === 'mysql_fulltext_search') {
                $class = 'MySQL_Fulltext_Search';
            } elseif ($class === 'sphinx_fulltext_search') {
                $class = 'Sphinx_Fulltext_Search';
            }

            return new $class();
        };
    }
}
