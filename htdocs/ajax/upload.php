<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

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

    $iaf_id = Attachment::addFiles($_FILES[$file]);
    $res = array(
        'error' => 0,
        'iaf_id' => $iaf_id,
    );
} catch (Exception $e) {
    $code = $e->getCode();
    $res = array(
        'error' => $code ? $code : -1,
        'message' => $e->getMessage(),
    );
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($res);
