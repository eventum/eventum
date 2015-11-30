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
use Exception;
use Logger;
use Prefs;

class FileUploadController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'file_upload.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $issue_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = (string) $request->request->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication('index.php?err=5', true);

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'upload_file') {
            $this->uploadFileAction();
        }
    }

    /**
     * handle uploads
     */
    private function uploadFileAction()
    {
        $post = $this->getRequest()->request;
        $usr_id = Auth::getUserID();

        // attachment status (public or internal)
        $status = $post->getAlpha('status');
        $internal_only = $status == 'internal';

        // from ajax upload, attachment file ids
        $iaf_ids = $post->has('iaf_ids') ? explode(',', $post->get('iaf_ids')) : null;
        // description for attachments
        $file_description = $post->get('file_description');

        // if no iaf_ids passed, perhaps it's old style upload
        // TODO: verify that the uploaded file(s) owner is same as attachment owner.
        if (!$iaf_ids && isset($_FILES['attachment'])) {
            $iaf_ids = Attachment::addFiles($_FILES['attachment']);
        }

        try {
            Attachment::attachFiles($this->issue_id, $usr_id, $iaf_ids, $internal_only, $file_description);
            $res = 1;
        } catch (Exception $e) {
            Logger::app()->error($e);
            $res = -1;
        }

        $this->tpl->assign('upload_file_result', $res);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'issue_id' => $this->issue_id,
                'current_user_prefs' => Prefs::get(Auth::getUserID()),
                'max_attachment_size' => Attachment::getMaxAttachmentSize(),
                'max_attachment_bytes' => Attachment::getMaxAttachmentSize(true),
            )
        );
    }
}
