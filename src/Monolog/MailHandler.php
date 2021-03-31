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

namespace Eventum\Monolog;

use Eventum\Config\Config;
use Eventum\ServiceContainer;
use Misc;
use Monolog;
use Monolog\Handler\NativeMailerHandler;
use Setup;

class MailHandler extends NativeMailerHandler
{
    /** @var Config */
    private $config;

    /**
     * Create mail handler for Eventum errors
     *
     * @param array|int|string $level
     */
    public function __construct($level = Monolog\Logger::ERROR)
    {
        $this->config = $this->getConfig();
        parent::__construct([], $this->config['subject'], Setup::getSmtpFrom(), $level);
        $this->setTo($this->config['addresses']);
    }

    public function setTo($to): self
    {
        if ($this->config['status'] === 'enabled') {
            $notify_list = trim($to);
            // recipient list can be comma separated
            $to = Misc::trim(explode(',', $notify_list));
        } else {
            $to = [];
        }

        $this->to = $to;

        return $this;
    }

    private function getConfig(): Config
    {
        return ServiceContainer::getConfig()['email_error'];
    }
}
