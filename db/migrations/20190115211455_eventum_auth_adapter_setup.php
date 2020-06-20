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
use Eventum\ServiceContainer;

class EventumAuthAdapterSetup extends AbstractMigration
{
    private const DEFAULT_ADAPTER = Adapter\Factory::DEFAULT_ADAPTER;

    /** @var array */
    private $classMapping = [
        'mysql_auth_backend' => Adapter\MysqlAdapter::class,
        'ldap_auth_backend' => Adapter\LdapAdapter::class,
        'cas_auth_backend' => Adapter\CasAdapter::class,
    ];

    public function up(): void
    {
        if ($this->hasFallbackEnabled()) {
            $this->classMapping['ldap_auth_backend'] = Adapter\ChainAdapter::class;
        }

        $this->setupAuthAdapter();
    }

    public function down(): void
    {
    }

    private function setupAuthAdapter(): void
    {
        $setup = ServiceContainer::getConfig();
        $setup['auth'] = $this->createConfiguration();
        Setup::save();
    }

    private function createConfiguration(): array
    {
        $reflection = new ReflectionClass($this->getAdapterClassName());
        $className = $reflection->getName();

        return [
            'adapter' => $className,
            'options' => [
                $className => $this->getAdapterArguments($className),
            ],
        ];
    }

    private function hasFallbackEnabled(): bool
    {
        return defined('APP_AUTH_BACKEND_ALLOW_FALLBACK') && APP_AUTH_BACKEND_ALLOW_FALLBACK;
    }

    private function getAdapterClassName(): string
    {
        if (!defined('APP_AUTH_BACKEND')) {
            return self::DEFAULT_ADAPTER;
        }

        $class = strtolower(APP_AUTH_BACKEND);

        return $this->classMapping[$class] ?? self::DEFAULT_ADAPTER;
    }

    private function getAdapterArguments(string $className): ?array
    {
        if ($className === Adapter\ChainAdapter::class) {
            return [
                [
                    Adapter\LdapAdapter::class,
                    Adapter\MysqlAdapter::class,
                ],
            ];
        }

        return null;
    }
}
