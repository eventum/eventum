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

namespace Eventum\Extension\Legacy;

use CRM;
use Eventum\Event\EventContext;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use User;

/**
 * Extension that adds integration of legacy Customer classes to Extension events
 */
class CustomerLegacyExtension implements Provider\SubscriberProvider, EventSubscriberInterface
{
    public function getSubscribers(): array
    {
        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /** @see CustomerLegacyExtension::newIssueParams */
            SystemEvents::ISSUE_CREATE_PARAMS => 'newIssueParams',
        ];
    }

    /**
     * @see Issue::createFromPost
     */
    public function newIssueParams(EventContext $event): void
    {
        if (!$this->hasCustomerIntegration($event)) {
            return;
        }

        // if we are creating an issue for a customer, put the
        // main customer contact as the reporter for it
        $contact_usr_id = User::getUserIDByContactID($event['contact']);
        if ($contact_usr_id) {
            $event['reporter'] = $contact_usr_id;
        }
    }

    private function hasCustomerIntegration(EventContext $event): bool
    {
        return CRM::hasCustomerIntegration($event->getProjectId());
    }
}
