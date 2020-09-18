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

namespace Eventum\Controller\Manage;

use Eventum\Mail\Imap\ImapConnection;
use RuntimeException;

class CheckEmailSettingsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'get_emails_ajax.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $post = $this->getRequest()->request;
        $hostname = $post->get('hostname');

        $account = [
            'ema_hostname' => $hostname,
            'ema_port' => $post->get('port'),
            'ema_type' => $post->get('type'),
            'ema_folder' => $post->get('folder'),
            'ema_username' => $post->get('username'),
            'ema_password' => $post->get('password'),
        ];

        try {
            $connection = new ImapConnection($account);
            $connection->isConnected();
        } catch (RuntimeException $e) {
            $this->tpl->assign('error', $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
