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
use Doctrine\Common\Cache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Eventum\Config\Paths;
use Eventum\Db\Types\UTCDateTimeType;
use Eventum\DebugBarManager;
use Eventum\Model\Repository;
use Misc;

/**
 * @method static Repository\CommitFileRepository getCommitFileRepository()
 * @method static Repository\CommitRepository getCommitRepository()
 * @method static Repository\CustomFieldRepository getCustomFieldRepository()
 * @method static Repository\EmailAccountRepository getEmailAccountRepository()
 * @method static Repository\IssueAssociationRepository getIssueAssociationRepository()
 * @method static Repository\IssueHistoryRepository getIssueHistoryRepository()
 * @method static Repository\IssuePartnerRepository getIssuePartnerRepository()
 * @method static Repository\IssueRepository getIssueRepository()
 * @method static Repository\PartnerProjectRepository getPartnerProjectRepository()
 * @method static Repository\ProjectRepository getProjectRepository()
 * @method static Repository\RemoteLinkRepository getRemoteLinkRepository()
 * @method static Repository\SearchProfileRepository getSearchProfileRepository()
 * @method static Repository\StatusRepository getStatusRepository()
 * @method static Repository\UserPreferenceRepository getUserPreferenceRepository()
 * @method static Repository\UserRepository getUserRepository()
 */
class Doctrine
{
    private const PROXY_CACHE_DIR = Paths::APP_CACHE_PATH . '/doctrine/proxies';

    public static function getEntityManager(): EntityManager
    {
        static $entityManager;
        if ($entityManager) {
            return $entityManager;
        }

        $isDevMode = false;
        $paths = [
            Paths::APP_PATH . '/src/Model/Entity',
        ];

        $config = DB_Helper::getConfig();
        $conn = [
            'driver' => 'pdo_mysql',
            'user' => $config['username'],
            'password' => $config['password'],
            'dbname' => $config['database'],
            'host' => $config['hostname'],
            'port' => $config['port'],
            'unix_socket' => $config['socket'] ?? null,
            'charset' => $config['charset'],
        ];

        $cacheDriver = new Cache\ArrayCache();
        $proxyDir = Misc::ensureDir(self::PROXY_CACHE_DIR);

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache = null, $useSimpleAnnotationReader = false);

        $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        // https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/caching.html#query-cache
        $config->setQueryCacheImpl($cacheDriver);
        // https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/caching.html#result-cache
        $config->setResultCacheImpl($cacheDriver);
        // https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/caching.html#metadata-cache
        $config->setMetadataCacheImpl($cacheDriver);

        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);

        $entityManager = EntityManager::create($conn, $config);

        DebugBarManager::getDebugBarManager()->registerDoctrine($entityManager);

        return $entityManager;
    }

    /**
     * Helper to return db dsn for symfony bridge
     */
    public static function getUrl(): ?string
    {
        $config = DB_Helper::getConfig();
        if (!$config) {
            return null;
        }

        return sprintf(
            'mysql://%s:%s@%s:%d/%s',
            $config['username'],
            $config['password'],
            $config['hostname'],
            $config['port'],
            $config['database']
        );
    }

    public static function __callStatic($method, array $arguments = [])
    {
        static $repos;

        if (preg_match('/get(\w+)Repository/', $method, $m)) {
            $class = '\\Eventum\\Model\\Entity\\' . $m[1];

            return $repos[$class] ?? $repos[$class] = self::getEntityManager()
                    ->getRepository($class);
        }

        throw new BadMethodCallException($method);
    }
}
