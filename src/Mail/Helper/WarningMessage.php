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

namespace Eventum\Mail\Helper;

use Closure;
use Eventum\Mail\MailMessage;
use Issue;
use Setup;
use Support;
use User;
use Zend\Mail\Exception\InvalidArgumentException;
use Zend\Mime;

class WarningMessage
{
    /** @var MailMessage */
    private $mail;

    public function __construct(MailMessage $mail)
    {
        $this->mail = $mail;
    }

    /**
     * Method used to add a customized warning message to the body
     * of outgoing emails.
     *
     * @param int $issue_id The issue ID
     * @param string $recipient_email The recipient email of the message
     */
    public function add($issue_id, $recipient_email)
    {
        if (!$this->enabled()) {
            return;
        }

        // check if the recipient can send emails to the customer
        if (!$this->shouldAddWarningMessage($issue_id, $recipient_email)) {
            return;
        }

        if (!$this->isAllowedToEmail($issue_id, $recipient_email)) {
            $warning = $this->getWarningMessage('blocked');
        } else {
            $warning = $this->getWarningMessage('allowed');
        }

        $this->modifyContent(
            function ($content) use ($warning) {
                return $warning . "\n\n" . $content;
            }
        );
    }

    public function remove()
    {
        if (!$this->enabled()) {
            return;
        }

        $blocked = $this->getWarningMessage('blocked');
        $warning = $this->getWarningMessage('allowed');

        $this->modifyContent(
            function ($content) use ($blocked, $warning) {
                $content = str_replace([$blocked . "\n\n", $warning . "\n\n"], '', $content);

                return $content;
            }
        );
    }

    /**
     * @param Closure $callback
     */
    protected function modifyContent(Closure $callback)
    {
        $mail = $this->mail;

        // Add these later, if needed
        if ($mail->isMultipart()) {
            throw new InvalidArgumentException('Multipart not supported');
        }

        if ($mail->getHeaders()->has('Content-Transfer-Encoding')) {
            $cte = $mail->ContentTransferEncoding;
            if ($cte != Mime\Mime::ENCODING_8BIT) {
                throw new InvalidArgumentException("Content-Transfer-Encoding '{$cte}' not supported");
            }
        }

        $content = $mail->getContent();
        $content = $callback($content);
        $mail->setContent($content);
    }

    /**
     * Local method to allow phpunit mocking
     *
     * @param int $issue_id
     * @param string $recipient_email
     * @return bool
     */
    protected function isAllowedToEmail($issue_id, $recipient_email)
    {
        return Support::isAllowedToEmail($issue_id, $recipient_email);
    }

    /**
     * @param int $issue_id
     * @param string $recipient_email
     * @return bool
     */
    protected function shouldAddWarningMessage($issue_id, $recipient_email)
    {
        $usr_id = User::getUserIDByEmail($recipient_email);
        // don't add the warning message if the recipient is an unknown email address
        if (!$usr_id) {
            return false;
        }

        // don't add anything if the recipient is a known customer contact
        $prj_id = Issue::getProjectID($issue_id);
        $role_id = User::getRoleByUser($usr_id, $prj_id);
        if ($role_id == User::ROLE_CUSTOMER) {
            return false;
        }

        return true;
    }

    /**
     * Returns the warning message that needs to be added to the top of routed
     * issue emails to alert the recipient that he can (or not) send emails to
     * the issue notification list.
     *
     * @param   string $type Whether the warning message is of an allowed recipient or not
     * @return  string The warning message
     */
    private function getWarningMessage($type)
    {
        if ($type == 'allowed') {
            $str = ev_gettext('ADVISORY: Your reply will be sent to the notification list.');
        } else {
            $str = ev_gettext('WARNING: If replying, add yourself to Authorized Repliers list first.');
        }

        return $str;
    }

    /**
     * @return bool return true if the feature is enabled
     */
    protected function enabled()
    {
        static $enabled;

        if ($enabled === null) {
            $setup = Setup::get();
            $enabled = $setup['email_routing']['status'] == 'enabled'
                && $setup['email_routing']['warning']['status'] == 'enabled';
        }

        return $enabled;
    }
}
