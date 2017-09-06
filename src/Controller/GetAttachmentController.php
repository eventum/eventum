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
use Eventum\Mail\MailMessage;
use InvalidArgumentException;
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
            $mail = Note::getBlockedMessage($get->getInt('note_id'));
        } else {
            $mail = Support::getSupportEmail($get->getInt('sup_id'));
        }

        if ($this->raw) {
            $email = $mail->getRawContent();
            Misc::outputDownload($email, 'message.eml', Misc::countBytes($email), 'message/rfc822');

            return;
        }

        $cid = $get->get('cid');
        $filename = $get->get('filename');
        $attachment = $this->getAttachment($mail, $filename, $cid);
        $bytes = Misc::countBytes($attachment['blob']);
        Misc::outputDownload($attachment['blob'], $filename, $bytes, $attachment['filetype']);
    }

    /**
     * Method used to get the encoded content of a specific message
     * attachment.
     *
     * @param MailMessage $mail The Mail object
     * @param string $filename The filename to look for
     * @param string $cid The content-id to look for, if any
     * @return array
     */
    private function getAttachment(MailMessage $mail, $filename, $cid = null)
    {
        $attachments = $mail->getAttachment()->getAttachments();
        foreach ($attachments as $attachment) {
            if ($cid && $attachment['cid'] == $cid) {
                return $attachment;
            }

            if ($attachment['filename'] == $filename) {
                return $attachment;
            }
        }

        throw new InvalidArgumentException('Attachment not found');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
