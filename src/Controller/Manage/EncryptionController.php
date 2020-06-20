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

use Eventum\Crypto\CryptoException;
use Eventum\Crypto\CryptoKeyManager;
use Eventum\Crypto\CryptoUpgradeManager;
use Eventum\ServiceContainer;
use User;

class EncryptionController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/encryption.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'regenerate':
                $this->regenerateAction();
                break;
            case 'activate':
                $this->activateAction();

                break;
        }
    }

    private function regenerateAction(): void
    {
        $cm = new CryptoUpgradeManager();

        try {
            // disable key, regenerate key and redirect to activate
            // can't do in same page request because macOS php server forever cache
            $cm->disable();
            $km = new CryptoKeyManager();
            $km->generateKey();
            $this->messages->addInfoMessage(ev_gettext('Thank you, new key for encryption was generated.'));
            $this->redirect('encryption.php', ['cat' => 'activate', 'encryption' => '1']);
        } catch (CryptoException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $error = ev_gettext('Unable to generate new encryption key. Check server error logs.');
            $this->messages->addErrorMessage($error);
        }

        $this->redirect('encryption.php');
    }

    private function activateAction(): void
    {
        $request = $this->getRequest();
        $enable = $request->request->get('encryption') ?: $request->query->get('encryption');
        $cm = new CryptoUpgradeManager();

        if (!$enable) {
            try {
                $cm->disable();
                $this->messages->addInfoMessage(ev_gettext('Encryption was disabled!'));
            } catch (CryptoException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                $error = ev_gettext('Encryption can not be disabled: %s', $e->getMessage());
                $this->messages->addErrorMessage($error);
            }
        } else {
            try {
                $cm->enable();
                $this->messages->addInfoMessage(ev_gettext('Encryption was enabled!'));
            } catch (CryptoException $e) {
                $error = ev_gettext('Encryption can not be enabled: %s', $e->getMessage());
                $this->messages->addErrorMessage($error);
            }
        }

        $this->redirect('encryption.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $setup = ServiceContainer::getConfig();
        $this->tpl->assign(
            [
                'encryption' => $this->html->enableRadioButtons($setup['encryption']),
            ]
        );
    }
}
