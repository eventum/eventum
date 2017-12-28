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
use Eventum\Attachment\AttachmentManager;
use Issue;
use User;

class DownloadController extends BaseController
{
    /** @var string */
    private $cat;

    /** @var int */
    private $iaf_id;

    /** @var int */
    private $usr_id;

    /** @var bool */
    private $force_inline;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->query->getAlpha('cat');
        $this->iaf_id = $request->query->getInt('id');
        $this->force_inline = $request->query->getBoolean('force_inline');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if (stripos(APP_BASE_URL, 'https:') !== false) {
            // fix for IE 5.5/6 with SSL sites
            header('Pragma: cache');
        }
        // fix for IE6 (KB812935)
        header('Cache-Control: must-revalidate');

        if ($this->cat === 'attachment') {
            $this->attachmentAction();
        }
    }

    private function attachmentAction()
    {
        $file = AttachmentManager::getAttachment($this->iaf_id);
        if (!$file) {
            $this->error(ev_gettext('No such attachment'));
        }

        $group = $file->getGroup();
        if (!Issue::canAccess($group->issue_id, $this->usr_id) ||
                User::getRoleByUser($this->usr_id, Issue::getProjectID($group->issue_id)) < $group->minimum_role) {
            $this->error(ev_gettext('No access to requested attachment'));
        }

        $file->outputDownload($this->force_inline);
        exit;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
