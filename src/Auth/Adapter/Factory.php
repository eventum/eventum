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

use InvalidArgumentException;
use ReflectionClass;
use Traversable;
use Zend\Stdlib\ArrayUtils;

abstract class Factory
{
    public const DEFAULT_ADAPTER = MysqlAdapter::class;

    /**
     * @param array $spec
     * @return AdapterInterface
     */
    public static function create($spec = []): AdapterInterface
    {
        if ($spec instanceof Traversable) {
            $spec = ArrayUtils::iteratorToArray($spec);
        }

        if (!is_array($spec)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects an array or Traversable argument; received "%s"',
                __METHOD__,
                (is_object($spec) ? get_class($spec) : gettype($spec))
            ));
        }

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
        return [
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
    }
}
