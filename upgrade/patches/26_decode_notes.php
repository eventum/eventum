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
 * Decode note bodies again which have failed to decode unicode html entities
 */

/** @var AdapterInterface $db */
/** @var Closure $log */

// notes that need to be decoded
$res = $db->getAll('select not_id, not_iss_id, not_is_blocked, not_created_date, not_note, not_full_message from {{%note}} where not_note like ?', array('%&#x00%'));

$render_diff = function ($old, $new) {
    $diff = new Text_Diff(explode(PHP_EOL, $old), explode(PHP_EOL, $new));
    $renderer = new Text_Diff_Renderer_unified();

    return $renderer->render($diff);
};

$now = Date_Helper::getCurrentDateGMT();
foreach ($res as $i => $row) {
    $email = Mime_Helper::decode($row['not_full_message'], true);
    $note = trim($email->body);
    if ($row['not_is_blocked']) {
        $note = Mail_Helper::getCannedBlockedMsgExplanation() . $note;
    }

    $diff = $render_diff($row['not_note'], $note);
    $log("--- issue #{$row['not_iss_id']} {$row['not_created_date']} GMT");
    $log("+++ issue #{$row['not_iss_id']} $now GMT");
    $log($diff);
    $db->query('UPDATE {{%note}} '.
        'SET not_note=? '.
        'WHERE not_id=?', array($note, $row['not_id'])
    );
}

$count = count($res);
$log("$count notes updated");
