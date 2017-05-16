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
     * Method invoked so the extension can setup class loader.
     *
     * @param \Composer\Autoload\ClassLoader $loader
     * @since 3.2.0
     */
    public function registerAutoloader($loader)
    {
    }

    /**
     * Get classes implementing EventSubscriberInterface.
     *
     * @see http://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers
     * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
     * @return string[]
     * @since 3.2.0
     */
    public function getSubscribers()
    {
        return [];
    }

    /**
     * Return Workflow Class names your extension provides.
     *
     * @return string[]
     * @since 3.2.0
     */
    public function getAvailableWorkflows()
    {
        return [];
    }

    /**
     * Return Custom Field Class names your extension provides.
     *
     * @return string[]
     * @since 3.2.0
     */
    public function getAvailableCustomFields()
    {
        return [];
    }

    /**
     * Return Partner Class names your extension provides.
     *
     * @return string[]
     * @since 3.2.0
     */
    public function getAvailablePartners()
    {
        return [];
    }

    /**
     * Return CRM Class names your extension provides.
     *
     * @return string[]
     * @since 3.2.0
     */
    public function getAvailableCRMs()
    {
        return [];
    }
}
