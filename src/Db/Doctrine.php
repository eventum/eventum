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
use Eventum\Model\Repository;
use Eventum\ServiceContainer;

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
    /**
     * @deprecated use ServiceContainer::getEntityManager()
     */
    public static function getEntityManager(): EntityManager
    {
        trigger_deprecation('eventum/eventum', '3.9.3', '%s::%s() is deprecated, use "ServiceContainer::getEntityManager()" instead.', __CLASS__, __METHOD__);

        return ServiceContainer::getEntityManager();
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

            return $repos[$class] ?? $repos[$class] = ServiceContainer::getEntityManager()
                    ->getRepository($class);
        }

        throw new BadMethodCallException($method);
    }
}
