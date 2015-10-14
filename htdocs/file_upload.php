<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

Auth::checkAuthentication('index.php?err=5', true);

$usr_id = Auth::getUserID();

$tpl = new Template_Helper();
$tpl->setTemplate('file_upload.tpl.html');

$issue_id = isset($_POST['issue_id']) ? $_POST['issue_id'] : $_GET['iss_id'];
$cat = isset($_POST['cat']) ? $_POST['cat'] : null;

// handle uploads
if ($cat == 'upload_file') {
    // attachment status (public or internal)
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    $internal_only = $status == 'internal';
    // from ajax upload, attachment file ids
    $iaf_ids = !empty($_POST['iaf_ids']) ? explode(',', $_POST['iaf_ids']) : null;
    // description for attachments
    $file_description = isset($_POST['file_description']) ? $_POST['file_description'] : null;

    // if no iaf_ids passed, perhaps it's old style upload
    // TODO: verify that the uploaded file(s) owner is same as attachment owner.
    if (!$iaf_ids && isset($_FILES['attachment'])) {
        $iaf_ids = Attachment::addFiles($_FILES['attachment']);
    }

    try {
        Attachment::attachFiles($issue_id, $usr_id, $iaf_ids, $internal_only, $file_description);
        $res = 1;
    } catch (Exception $e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
        $res = -1;
    }

    $tpl->assign('upload_file_result', $res);
}

$tpl->assign(array(
    'issue_id' => $issue_id,
    'current_user_prefs' => Prefs::get(Auth::getUserID()),
    'max_attachment_size' => Attachment::getMaxAttachmentSize(),
    'max_attachment_bytes' => Attachment::getMaxAttachmentSize(true),
));

$tpl->displayTemplate();
