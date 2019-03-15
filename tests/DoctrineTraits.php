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

namespace Eventum\Test;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Eventum\Db\Doctrine;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

trait DoctrineTraits
{
    protected function getEntityManager(): EntityManager
    {
        return Doctrine::getEntityManager();
    }

    protected function persistAndFlush($object): void
    {
        $em = $this->getEntityManager();
        $em->persist($object);
        $em->flush($object);
    }

    /**
     * Solution to set Id Explicitly  when using "AUTO" strategy.
     * @see https://stackoverflow.com/q/5301285/2314626
     * @param object $entity
     * @param int $id
     * @throws ReflectionException
     */
    protected function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $attribute = new ReflectionProperty($entity, 'id');

        try {
            $attribute->setAccessible(true);
            if ($attribute->getValue($entity)) {
                return;
            }
            $attribute->setValue($entity, $id);
        } finally {
            $attribute->setAccessible(false);
        }

        // allow to use provided id
        $metadata = $this->getEntityManager()->getClassMetadata($reflection->getName());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new AssignedGenerator());
    }
}
