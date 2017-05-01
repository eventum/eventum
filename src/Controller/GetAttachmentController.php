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

use Attachment;
use Auth;
use Mime_Helper;
use Misc;
use Note;
use Support;

class GetAttachmentController extends BaseController
{
    /** @var string */
    private $cat;

    /** @var bool */
    private $raw;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $get = $this->getRequest()->query;

        $this->cat = $get->getAlpha('cat');
        $this->raw = $get->getBoolean('raw');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $get = $this->getRequest()->query;

        if ($this->cat == 'blocked_email') {
            $email = Note::getBlockedMessage($get->getInt('note_id'));
        } else {
            $email = Support::getFullEmail($get->getInt('sup_id'));
        }

        if ($this->raw) {
            Attachment::outputDownload($email, 'message.eml', Misc::countBytes($email), 'message/rfc822');
        }

        $cid = $get->get('cid');
        $filename = $get->get('filename');
        if ($cid) {
            list($mimetype, $data) = Mime_Helper::getAttachment($email, $filename, $cid);
        } else {
            list($mimetype, $data) = Mime_Helper::getAttachment($email, $filename);
        }
        Attachment::outputDownload($data, $filename, Misc::countBytes($data), $mimetype);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
