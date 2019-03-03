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

namespace Eventum\Controller\Ajax;

use AuthCookie;
use Eventum\Attachment\AttachmentManager;
use Eventum\Controller\BaseController;
use InvalidArgumentException;
use Throwable;

class UploadController extends BaseController
{
    /** @var string */
    protected $tpl_name;
    /** @var string */
    private $file;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->file = $request->query->get('file');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        // check if logged in. if not, just give error
        if (!AuthCookie::hasAuthCookie()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        try {
            $res = $this->uploadAction();
        } catch (Throwable $e) {
            $code = $e->getCode();
            $res = [
                'error' => $code ? $code : -1,
                'message' => $e->getMessage(),
            ];
            $this->logger->error($e);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($res);
    }

    /**
     * FIXME: no identity logged who added the file.
     */
    protected function uploadAction(): array
    {
        if ($this->file) {
            // TRANSLATORS: this is technical error and should not be displayed to end users
            throw new InvalidArgumentException(ev_gettext('No file argument'));
        }

        if (!isset($_FILES[$this->file])) {
            throw new InvalidArgumentException(ev_gettext('No files uploaded'));
        }

        $iaf_ids = AttachmentManager::addFiles($_FILES[$this->file]);

        return [
            'error' => 0,
            'iaf_id' => $iaf_ids,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        exit(0);
    }
}
