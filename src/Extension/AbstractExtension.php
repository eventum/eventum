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

namespace Eventum\Extension;

/**
 * Class AbstractExtension
 *
 * Class doc comment will be used to describe purpose or add documentation.
 *
 * $d = new \ReflectionClass('foo');
 * $d->getDocComment();
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     * @see ExtensionInterface::registerAutoloader()
     */
    public function registerAutoloader($loader)
    {
    }

    /**
     * {@inheritdoc}
     * @see ExtensionInterface::getSubscribers()
     */
    public function getSubscribers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see ExtensionInterface::getAvailableWorkflows()
     */
    public function getAvailableWorkflows()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see ExtensionInterface::getAvailableCustomFields()
     */
    public function getAvailableCustomFields()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see ExtensionInterface::getAvailablePartners()
     */
    public function getAvailablePartners()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see ExtensionInterface::getAvailableCRMs()
     */
    public function getAvailableCRMs()
    {
        return [];
    }
}
