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
use MySQL_Fulltext_Search;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Sphinx_Fulltext_Search;
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

        $app[MySQL_Fulltext_Search::class] = static function ($app) {
            return new MySQL_Fulltext_Search($app['db']);
        };

        $app[Sphinx_Fulltext_Search::class] = static function ($app) {
            return new Sphinx_Fulltext_Search($app[SphinxClient::class]);
        };

        $app[Abstract_Fulltext_Search::class] = static function ($app) {
            $className = $app['config']['fulltext_search_class'];

            // legacy: handle lowercased classname
            if ($className === 'mysql_fulltext_search') {
                $className = MySQL_Fulltext_Search::class;
            } elseif ($className === 'sphinx_fulltext_search') {
                $className = Sphinx_Fulltext_Search::class;
            }

            return $app[$className];
        };
    }
}
