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

namespace Example\Subscriber;

use Eventum\Event\SystemEvents;
use Example\ExampleExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class PhinxConfig implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::PHINX_CONFIG => 'phinxConfig',
        ];
    }

    public function phinxConfig(GenericEvent $event): void
    {
        $phinx = $event->getArguments();
        $phinx['paths']['migrations'][] = $this->getMigrationPath();
        $event->setArguments($phinx);
    }

    private function getMigrationPath()
    {
        return sprintf('%s/db/migrations', ExampleExtension::EXTENSION_DIR);
    }
}
