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

use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\Mail\MailDumper;
use Eventum\Mail\MailMessage;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailDumperExtension implements SubscriberProvider, EventSubscriberInterface
{
    /** @var string|null */
    private $path;

    public function __construct()
    {
        $this->path = ServiceContainer::getConfig()['routed_mails_savedir'];
    }

    public function getSubscribers(): array
    {
        if (!$this->path) {
            return [];
        }

        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::MAIL_ROUTE_NOTE => 'dumpNote',
            SystemEvents::MAIL_ROUTE_DRAFT => 'dumpDraft',
            SystemEvents::MAIL_ROUTE_EMAIL => 'dumpEmail',
        ];
    }

    public function dumpDraft(MailMessage $mail): void
    {
        $dumper = new MailDumper($this->path . '/routed_drafts');
        $dumper->dump($mail);
    }

    public function dumpEmail(MailMessage $mail): void
    {
        $dumper = new MailDumper($this->path . '/routed_emails');
        $dumper->dump($mail);
    }

    public function dumpNote(MailMessage $mail): void
    {
        $dumper = new MailDumper($this->path . '/routed_notes');
        $dumper->dump($mail);
    }
}
