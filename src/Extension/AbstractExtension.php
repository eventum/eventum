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
 * @deprecated [since 3.6.6]: implement each interface by your own
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     * @see Provider\AutoloadProvider::registerAutoloader()
     */
    public function registerAutoloader($loader): void
    {
    }

    /**
     * {@inheritdoc}
     * @see Provider\SubscriberProvider::getSubscribers()
     */
    public function getSubscribers(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see Provider\WorkflowProvider::getAvailableWorkflows()
     */
    public function getAvailableWorkflows(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see Provider\CustomFieldProvider::getAvailableCustomFields()
     */
    public function getAvailableCustomFields(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see Provider\PartnerProvider::getAvailablePartners()
     */
    public function getAvailablePartners(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @see Provider\CrmProvider::getAvailableCRMs()
     */
    public function getAvailableCRMs(): array
    {
        return [];
    }
}
