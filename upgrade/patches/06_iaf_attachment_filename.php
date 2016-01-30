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

use Eventum\Db\Adapter\AdapterInterface;

/**
 * Decode attachment filenames from QuotedPrintable MIME encoding.
 * Also set Untitled.jpg to unnamed attachments (Usually inline).
 */

/** @var AdapterInterface $db */

// Attachments that need to be decoded
$res = $db->getAll('SELECT iaf_id, iaf_filename FROM {{%issue_attachment_file}} WHERE iaf_filename LIKE ?', array('%=?%'));

foreach ($res as $idx => $row) {
    $iaf_filename = Mime_Helper::decodeQuotedPrintable($row['iaf_filename']);
    $db->query('UPDATE {{%issue_attachment_file}} '.
        'SET iaf_filename=? '.
        'WHERE iaf_id=?', array($iaf_filename, $row['iaf_id'])
    );
}

// Unnamed attachments
$res = $db->getAll("SELECT iaf_id, iaf_filetype FROM {{%issue_attachment_file}} WHERE iaf_filename=''");

foreach ($res as $idx => $row) {
    list($type, $ext) = explode('/', $row['iaf_filetype']);
    $iaf_filename = ev_gettext('Untitled.%s', $ext);

    $db->query('UPDATE {{%issue_attachment_file}} '.
        'SET iaf_filename=? '.
        'WHERE iaf_id=?', array($iaf_filename, $row['iaf_id'])
    );
}
