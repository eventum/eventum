<?php
/**
 * Try to find placeholders to old history entries not containing context information
 */

/** @var DbInterface $db */
$res = $db->getAll("select his_id,his_summary from eventum_issue_history where his_context='' limit 10");

# initial pattern list extracted using xgettext:
# $ grep -rl History::add lib htdocs | xargs sed -i -e 's/History::add/History__add/g'; \
# $ find -name '*.php' | grep -v localization | xgettext -L PHP --files-from=- --keyword=History__add:4
# and then some sed magic:
# :%s#\([(.)]\)#\\\1#g
# :%s#{\([^}]\+\)}#(?P<\1>.+)#g
# :%s#^msgid "#"/^#
# :%s#"$#$/",
#
$patterns = array(
    '/^Note added by (?P<subject>.+)$/',
    '/^Issue opened by (?P<subject>.+)$/',
    '/^Issue updated \((?P<changes>.+)\) by (?P<subject>.+)$/',
    '/^Time tracking entry submitted by (?P<subject>.+)$/',
    '/^Issue assigned to (?P<assignee>.+) by (?P<assigner>.+)$/',
    '/^Notification list entry \((?P<entry>.+)\) added by (?P<assigner>.+)/',
    "/^Status changed to '(?P<status>.+)' by (?P<subject>.+) when (?P<action>.+)$/",
    '/^Note converted to e-mail \(from: (?P<from>.+)\) by (?P<subject>.+)/',
);

// find contexts from history entries
$find = function($string) use ($patterns) {
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
    $context = array();
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
    return array(
        'message' => $message,
        'context' => $context,
    );
};

$i = 0;
foreach ($res as $i => $row) {
    $m = $find($row['his_summary']);
    if (!$m) {
        echo "{$row['his_summary']}\n";
    } else {
        print_r($m);
    }
}
die("$i rows processed\n");
