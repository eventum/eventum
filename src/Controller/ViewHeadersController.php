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

namespace Eventum\Controller;

use Auth;
use Note;
use Support;

class ViewHeadersController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'view_headers.tpl.html';

    /** @var int */
    private $id;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->id = $request->query->getInt('id');
        $this->cat = $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        if ($this->cat == 'note') {
            $mail = Note::getBlockedMessage($this->id);
        } else {
            $mail = Support::getSupportEmail($this->id);
        }
        $this->tpl->assign('headers', $mail->getRawContent());
    }
}
