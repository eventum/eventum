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

namespace Eventum\Db;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class Doctrine
{
    public static function getEntityManager()
    {
        static $entityManager;
        if ($entityManager) {
            return $entityManager;
        }

        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode = true;
        $proxyDir = APP_PATH . '/src/Doctrine/Proxy';
        $paths = [
            APP_PATH . '/src/Doctrine',
            APP_PATH . '/src/Model/Entity',
            ];
        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir);
        // or if you prefer yaml or XML
        //$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
        //$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

        // database configuration parameters
        $conn = [
            'driver' => 'pdo_mysql',
            'user' => 'mysql',
            'password' => '',
            'dbname' => 'eventum',
        ];

        // obtaining the entity manager
        $entityManager = EntityManager::create($conn, $config);

        return $entityManager;
    }
}
