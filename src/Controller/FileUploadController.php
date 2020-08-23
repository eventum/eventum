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
use Eventum\Attachment\AttachmentManager;
use Exception;
use User;

class FileUploadController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'file_upload.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $issue_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = (string) $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat === 'upload_file') {
            $this->uploadFileAction();
        }
    }

    /**
     * handle uploads
     */
    private function uploadFileAction(): void
    {
        $post = $this->getRequest()->request;
        $usr_id = Auth::getUserID();

        $minimum_role = $post->getInt('minimum_role', User::ROLE_VIEWER);

        $iaf_ids = $this->attach->getAttachedFileIds();
        // description for attachments
        $file_description = $post->get('file_description');

        try {
            AttachmentManager::attachFiles($this->issue_id, $usr_id, $iaf_ids, $minimum_role, $file_description);
            $res = 1;
        } catch (Exception $e) {
            $this->logger->error($e);
            $res = -1;
        }

        $this->tpl->assign('upload_file_result', $res);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'max_attachment_size' => AttachmentManager::getMaxAttachmentSize(),
                'max_attachment_bytes' => AttachmentManager::getMaxAttachmentSize(true),
                'roles' => User::getRoles(),
            ]
        );
    }
}
