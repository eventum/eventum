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

use Eventum\Auth\Adapter;
use Eventum\Db\AbstractMigration;

class EventumAuthAdapterSetup extends AbstractMigration
{
    private const DEFAULT_ADAPTER = Adapter\Factory::DEFAULT_ADAPTER;
    private const CLASS_MAPPING = [
        'mysql_auth_backend' => Adapter\MysqlAdapter::class,
        'ldap_auth_backend' => Adapter\ChainAdapter::class,
        'cas_auth_backend' => Adapter\CasAdapter::class,
    ];

    public function up()
    {
        $this->setupAuthAdapter();
    }

    private function setupAuthAdapter()
    {
        $setup = Setup::get();
        $className = $this->getClassName();
        $reflection = new ReflectionClass($className);
        if (!isset($setup['auth'])) {
            $setup['auth'] = [];
        }
        $setup['auth']['adapter'] = $reflection->getName();
        $setup['auth']['arguments'] = $this->getArguments($setup['auth']['adapter']);
        Setup::save();
    }

    private function getClassName()
    {
        $class = self::DEFAULT_ADAPTER;
        if (!defined('APP_AUTH_BACKEND')) {
            return $class;
        }

        $class = strtolower(APP_AUTH_BACKEND);

        return self::CLASS_MAPPING[$class] ?? self::DEFAULT_ADAPTER;
    }

    private function getArguments(string $className)
    {
        if ($className === Adapter\ChainAdapter::class) {
            return [
                Adapter\LdapAdapter::class,
                Adapter\MysqlAdapter::class,
            ];
        }

        return [];
    }
}
