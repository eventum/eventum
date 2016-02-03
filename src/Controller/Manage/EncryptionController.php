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
use Eventum\Crypto\CryptoManager;
use Misc;
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
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'regenerate') {
            $this->regenerateAction();
        }
    }

    private function regenerateAction()
    {
        try {
            CryptoManager::regenerateKey();
            Misc::setMessage(ev_gettext('Thank you, new key for encryption was generated.'));
        } catch (CryptoException $e) {
            Misc::setMessage(ev_gettext('Unable to generate new encryption key. Check server error logs.'), Misc::MSG_ERROR);
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
