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

namespace Eventum\Controller\Helper;

use Attachment;
use Eventum\Attachment\AttachmentManager;
use Symfony\Component\HttpFoundation\Request;

class AttachHelper
{
    /** @var Request */
    private $request;

    public function getAttachedFileIds()
    {
        $post = $this->request->request;

        // from ajax upload, attachment file ids
        $iaf_ids = $post->get('iaf_ids') ? explode(',', $post->get('iaf_ids')) : null;

        // if no iaf_ids passed, perhaps it's old style upload
        // TODO: verify that the uploaded file(s) owner is same as attachment owner.
        if (!$iaf_ids && isset($_FILES['attachment'])) {
            $iaf_ids = AttachmentManager::addFiles($_FILES['attachment']);
        }

        return $iaf_ids;
    }
}
