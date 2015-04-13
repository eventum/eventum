<?php
/**
 * Decode note bodies again which have failed to decode unicode html entities
 */

// notes that need to be decoded
$res = $db->getAll('select not_id, not_iss_id, not_is_blocked, not_created_date, not_note, not_full_message from {{%note}} where not_note like ?', array('%&#x00%'));

$render_diff = function($old, $new) {
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
    echo "--- issue #{$row['not_iss_id']} {$row['not_created_date']} GMT\n";
    echo "+++ issue #{$row['not_iss_id']} $now GMT\n";
    echo $diff;
    $db->query('UPDATE {{%note}} '.
        'SET not_note=? '.
        'WHERE not_id=?', array($note, $row['not_id'])
    );
}
echo count($res), " notes updated\n";
