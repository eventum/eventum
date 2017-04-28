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
 * Try to find placeholders to old history entries not containing context information
 */

# initial pattern list extracted using xgettext:
# $ grep -rl History::add lib htdocs | xargs sed -i -e 's/History::add/History__add/g'; \
# $ find -name '*.php' | grep -v localization | xgettext -L PHP --files-from=- --keyword=History__add:4
# $ msgcat --width=2000 eventum.pot | grep msgid
#
# and then some sed magic:
# :%s#\([(.)]\)#\\\1#g
# :%s#{\([^}]\+\)}#(?P<\1>.*)#g
# :%s#^msgid "#    "/^#
# :%s#"$#$/",
#

$patterns = [
    '/^Attachment removed by (?P<user>.*)$/',
    '/^Attachment uploaded by (?P<user>.*)$/',
    '/^Authorized replier (?P<replier>.*) removed by (?P<user>.*)$/',
    '/^Contract changed from (?P<old_contract_id>.*) to (?P<contract_id>.*) by (?P<user>.*)$/',
    "/^Custom field updated \((?P<changes>.*)\) by (?P<user>.*)$/",
    '/^Draft routed from (?P<from>.*)$/',
    '/^Duplicate flag was reset by (?P<user>.*)$/',
    "/^Email \(subject: '(?P<subject>.*)'\) associated by (?P<user>.*)$/",
    "/^Email \(subject: '(?P<subject>.*)'\) disassociated by (?P<user>.*)$/",
    "/^Email from '(?P<from>.*)' blocked\.?$/",
    '/^Email message draft updated by (?P<user>.*)$/',
    '/^Email message saved as a draft by (?P<user>.*)$/',
    '/^Email routed from (?P<from>.*)$/',
    "/^Group changed \((?P<changes>.*)\) by (?P<user>.*)$/",
    '/^Impact analysis removed by (?P<user>.*)$/',
    '/^Impact analysis submitted by (?P<user>.*)$/',
    '/^Initial Impact Analysis for issue set by (?P<user>.*)$/',
    '/^Issue assigned to (?P<assignee>.*) by (?P<user>.*)$/',
    "/^Issue assignment to changed \((?P<changes>.*)\) by (?P<user>.*)$/",
    '/^Issue assignments removed by (?P<user>.*)$/',
    '/^Issue associated to Issue #(?P<associated_id>.*) by (?P<user>.*)$/',
    '/^ ?Issue association to Issue #(?P<issue_id>.*) removed by (?P<user>.*)$/',
    '/^Issue associations removed by (?P<user>.*)$/',
    "/^Issue auto-assigned to (?P<assignee>.*) \(RR\)$/",
    "/^Issue auto-assigned to (?P<assignee>.*) \(TAM\)$/",
    '/^Issue cloned from #(?P<issue_id>.*)$/',
    '/^Issue cloned to #(?P<issue_id>.*)$/',
    "/^Issue manually set to status '(?P<status>.*)' by (?P<user>.*)$/",
    '/^Issue marked as a duplicate of issue #(?P<issue_id>.*) by (?P<user>.*)$/',
    '/^Issue opened anonymously$/',
    '/^Issue opened by (?P<sender>.*)$/',
    '/^Issue opened by (?P<user>.*)$/',
    '/^Issue quarantine status cleared by (?P<user>.*)$/',
    '/^Issue remotely assigned to (?P<assignee>.*) by (?P<user>.*)$/',
    "/^Issue updated \(Expected Resolution Date: (?P<changes>.*)\) by (?P<user>.*)$/",
    "/^Issue updated \((?P<changes>.*)\) by (?P<user>.*)$/",
    "/^Issue updated to status '(?P<status>.*)' by (?P<user>.*)$/",
    '/^New requirement submitted by (?P<user>.*)$/',
    '/^Note added by (?P<subject>.*)$/',
    "/^Note converted to draft \(from: (?P<from>.*)\) by (?P<user>.*)$/",
    "/^Note converted to e-mail \(from: (?P<from>.*)\) by (?P<user>.*)$/",
    '/^Note removed by (?P<user>.*)$/',
    '/^Note routed from (?P<user>.*)$/',
    "/^Notification list entry \('(?P<subscriber>.*)'\) added by (?P<user>.*)$/",
    "/^Notification list entry \('(?P<subscriber>.*)'\) updated by (?P<user>.*)$/",
    "/^Notification list entry \((?P<email>.*)\) added by (?P<user>.*)$/",
    "/^Notification list entry \((?P<email>.*)\) removed by (?P<user>.*)$/",
    '/^Outgoing email sent by (?P<user>.*)$/',
    "/^Partner '(?P<partner>.*)' added to issue by (?P<user>.*)$/",
    "/^Partner '(?P<partner>.*)' removed from issue by (?P<user>.*)$/",
    '/^Phone Support entry removed by (?P<user>.*)$/',
    '/^Phone Support entry submitted by (?P<user>.*)$/',
    '/^Reporter was changed to (?P<email>.*) by (?P<user>.*)$/',
    "/^SCM Checkins associated by SCM user '(?P<user>.*)'\.?$/",
    '/^SCM Checkins removed by (?P<user>.*)$/',
    "/^Status changed to '(?P<status>.*)' by (?P<user>.*) when sending a note$/",
    "/^Status changed to '(?P<status>.*)' by (?P<user>.*) when sending an email$/",
    "/^Status remotely changed to '(?P<status>.*)' by (?P<user>.*)$/",
    "/^The details for issue #(?P<issue_id>.*) were updated by (?P<user>.*) and the changes propagated to the duplicated issues\.?$/",
    '/^Time tracking entry removed by (?P<user>.*)$/',
    '/^Time tracking entry submitted by (?P<user>.*)$/',
    '/^Time tracking entry submitted remotely by (?P<user>.*)$/',
    '/^(?P<email>.*) added to the authorized repliers list by (?P<user>.*)$/',
    '/^(?P<other_user>.*) added to the authorized repliers list by (?P<user>.*)$/',
    '/^(?P<other_user>.*) removed from issue by (?P<user>.*)$/',
    '/^(?P<replier>.*) remotely added to authorized repliers by (?P<user>.*)$/',

    // manual matches from older eventum versions
    '/^Custom field updated by (?P<user>.+)$/',
    "/^Status changed to '(?P<status>.+)' by (?P<user>.+)$/",
    // some nasty errors
    "/^SCM Checkins associated by SCM user \\\\'(?P<user>.+)'\.$/",
    '/^SCM Checkins associated by SCM user "(?P<user>.+)"$/',

    // custom workflow methods
    '/^Status changed to "(?P<status>.+)" through assignment change$/',
    "/^Status changed from '(?P<old_status>.+)' to '(?P<new_status>.+)' because (?P<user>.+) assigned the issue and is the only assignee\.?$/",
];

// find contexts from history entries
$find = function ($string) use ($patterns) {
    $matches = null;
    // first find pattern that matches
    foreach ($patterns as $regex) {
        if (preg_match($regex, $string, $matches, PREG_OFFSET_CAPTURE)) {
            break;
        }
    }

    if (!$matches) {
        // y0, nothing matched
        return null;
    }

    $message = '';
    $context = [];
    $offset = 0;
    foreach ($matches as $token => $match) {
        // want only named captures
        if (is_numeric($token)) {
            continue;
        }
        // stuff preceding the match
        $message .= substr($string, $offset, $match[1] - $offset);
        // record value for context
        $context[$token] = $match[0];
        // append placeholder
        $message .= "{{$token}}";
        // update offset for next iteration
        $offset = $match[1] + strlen($match[0]);
    }
    // copy rest of the string
    $message .= substr($string, $offset);

    // give it out
    return [
        'message' => $message,
        'context' => $context,
    ];
};

/** @var AdapterInterface $db */
$his_ids = $db->getColumn("SELECT his_id FROM {{%issue_history}} WHERE his_context=''");
$total = count($his_ids);
$current = $updated = 0;

if (!$total) {
    // nothing to do
    return;
}

/** @var Closure $log */
$log("Total $total rows, this may take time. Please be patient.");

foreach ($his_ids as $his_id) {
    $his_summary = $db->getOne('SELECT his_summary FROM {{%issue_history}} WHERE his_id=?', [$his_id]);

    $current++;
    $m = $find($his_summary);
    if (!$m) {
        $log("No substitution: {$his_id} '{$his_summary}'");
        continue;
    }

    $context = json_encode($m['context']);
    if (!$context) {
        $log("{$his_id}: context encode failure; skipping");
        continue;
    }
    $db->query(
        'update {{%issue_history}} set his_summary=?, his_context=? where his_id=?', [
        $m['message'], $m['context'], $his_id,
    ]
    );
    $updated++;

    if ($current % 5000 == 0) {
        $p = round($current / $total * 100, 2);
        $log("... updated $current rows, $p%");
    }
}

$log("$current history entries matched, $updated updated");
