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

use BadMethodCallException;
use DB_Helper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Eventum\Model\Entity;
use Eventum\Model\Repository;

/**
 * @method static Repository\CommitFileRepository getCommitFileRepository()
 * @method static Repository\CommitRepository getCommitRepository()
 * @method static Repository\IssueAssociationRepository getIssueAssociationRepository()
 * @method static Repository\IssueCommitRepository getIssueCommitRepository()
 * @method static Repository\ProjectRepository getProjectRepository()
 */
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
            APP_PATH . '/src/Model/Entity',
        ];

        // database configuration parameters
        $config = DB_Helper::getConfig();
        $conn = [
            'driver' => 'pdo_mysql',
            'user' => $config['username'],
            'password' => $config['password'],
            'dbname' => $config['database'],
            'host' => $config['hostname'],
            'port' => $config['port'],
            'unix_socket' => isset($config['socket']) ? $config['socket'] : null,
            'charset' => 'utf8',
        ];

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir);

        // obtaining the entity manager
        $entityManager = EntityManager::create($conn, $config);

        return $entityManager;
    }

    public static function __callStatic($method, $arguments = [])
    {
        static $repos;

        if (preg_match('/get(\w+)Repository/', $method, $m)) {
            $class = '\\Eventum\\Model\\Entity\\' . $m[1];

            return $repos[$class]
                ?: $repos[$class] = self::getEntityManager()
                    ->getRepository($class);
        }

        throw new BadMethodCallException($method);
    }
}
