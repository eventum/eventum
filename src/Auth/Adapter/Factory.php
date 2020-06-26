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

namespace Eventum\Auth\Adapter;

use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;
use ReflectionClass;

abstract class Factory
{
    public const DEFAULT_ADAPTER = MysqlAdapter::class;

    /**
     * @param array $spec
     * @return AdapterInterface
     */
    public static function create(array $spec = []): AdapterInterface
    {
        $className = $spec['adapter'] ?? self::DEFAULT_ADAPTER;
        $arguments = $spec['options'][$className] ?? [];
        $reflection = new ReflectionClass($className);

        /** @var AdapterInterface $adapter */
        $adapter = $reflection->newInstanceArgs($arguments);

        return $adapter;
    }

    /**
     * Return list of possible adapters with their default options.
     *
     * @return array
     */
    public static function getAdapterList(): array
    {
        $adapters = [
            MysqlAdapter::class => [],
            LdapAdapter::class => [],
            CasAdapter::class => [],
            ChainAdapter::class => [
                [
                    MysqlAdapter::class,
                    LdapAdapter::class,
                ],
            ],
        ];

        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        foreach ($em->getAvailableAuthAdapters() as $className => $options) {
            if (is_numeric($className)) {
                $className = $options;
                $options = [];
            }
            $adapters[$className] = $options;
        }

        return $adapters;
    }
}
