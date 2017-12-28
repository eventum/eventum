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

use Eventum\Attachment\AttachmentManager;
use Eventum\Monolog\Logger;

require_once __DIR__ . '/../../init.php';

// handle ajax upload
// FIXME: no identity logged who added the file.
try {
    // check if logged in. if not, just give error
    if (!AuthCookie::hasAuthCookie()) {
        throw new BadFunctionCallException(ev_gettext('Must be logged in'));
    }

    if (!isset($_GET['file'])) {
        // TRANSLATORS: this is technical error and should not be displayed to end users
        throw new InvalidArgumentException(ev_gettext('No file argument'));
    }

    $file = (string) $_GET['file'];
    if (!isset($_FILES[$file])) {
        throw new InvalidArgumentException(ev_gettext('No files uploaded'));
    }

    $iaf_ids = AttachmentManager::addFiles($_FILES[$file]);
    $res = [
        'error' => 0,
        'iaf_id' => $iaf_ids,
    ];
} catch (Exception $e) {
    $code = $e->getCode();
    $res = [
        'error' => $code ? $code : -1,
        'message' => $e->getMessage(),
    ];
    Logger::app()->error($e);
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($res);
