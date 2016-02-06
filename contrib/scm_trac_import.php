#!/usr/bin/php
<?php

require_once __DIR__ . '/../init.php';

/**
 * find svn repository id, type must be empty (default) or 'svn'
 */
function getRepositoryId()
{
    global $db;

    $sth = $db->query("SELECT * FROM repository WHERE name='type' AND (value='' OR value='svn')");
    $row = $sth->fetch();

    if (!$row) {
        // if nothing, find repo whose name is not set the "(default)"
        $sth = $db->query("SELECT * FROM repository WHERE name='name' AND value=''");
        $row = $sth->fetch();
    }

    return $row['id'];
}

/**
 * parse the commit message and get all issue numbers we can find
 *
 * @param string $commit_msg
 * @return null|array
 */
function parseIssueIds($commit_msg)
{
    preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $commit_msg, $matches);
    if (!isset($matches[1])) {
        return null;
    }

    return $matches[1];
}

function expandPath($filename)
{
    // special for "dirname/" case, pathinfo would set dir to '.' and filename to 'dirname'
    $length = strlen($filename);
    if ($filename[$length - 1] == '/') {
        return array(rtrim($filename, '/'), '');
    }

    $fi = pathinfo($filename);

    return array($fi['dirname'], $fi['basename']);
}

function getRevInfo($repository_id, $revision_id)
{
    global $db;

    $sth = $db->prepare('SELECT * FROM node_change WHERE repos=? AND rev=?');
    $sth->execute(array($repository_id, $revision_id));

    $files = array();
    while ($change = $sth->fetch(PDO::FETCH_ASSOC)) {
        list($module, $filename) = expandPath($change['path']);

        $base_rev = (int) ltrim($change['base_rev'], '0');
        $rev = (int) ltrim($change['rev'], '0');
        $file = array(
            'file'        => $filename,
            'old_version' => $base_rev >= 0 ? $base_rev : null,
            'new_version' => $rev,
            'module'      => $module,
        );
        $files[] = $file;
    }

    return $files;
}

/**
 * Process all commits from trac repository $repository_id matching issue association commits
 *
 * @param int $repository_id
 */
function processCommits($repository_id)
{
    global $db, $scm_name;

    $sth = $db->prepare('SELECT * FROM revision WHERE repos=? AND message LIKE ?');
    $sth->execute(array($repository_id, '%issue%'));

    $nissues = $ncommits = 0;

    while ($commit = $sth->fetch(PDO::FETCH_ASSOC)) {
        $issues = parseIssueIds($commit['message']);
        if (!$issues) {
            echo "Skipping {$commit['rev']} (no issue id in commit message): {$commit['message']}\n";
            continue;
        }

        $ts = Date_Helper::getDateTime((int) ($commit['time'] / 1000000), 'GMT');
        $commit_time = $ts->format('Y-m-d H:i:s');
        $files = getRevInfo($repository_id, $commit['rev']);

        foreach ($issues as $issue_id) {
            foreach ($files as $file) {
                TracScm::importCheckin(
                    $issue_id, $commit_time, $scm_name, $file, $commit['author'], $commit['message']
                );
                $ncommits++;
            }
            $nissues++;
        }
    }

    echo "Added $ncommits commits to $nissues issues\n";
}

class TracScm extends SCM
{
    /**
     * call insertCheckin to avoid touching issues history timestamps or invoking workflows that could reopen the issues
     */
    public static function importCheckin($issue_id, $commit_time, $scm_name, $file, $username, $commit_msg)
    {
        return self::insertCheckin($issue_id, $commit_time, $scm_name, $file, $username, $commit_msg);
    }
}

$dbfile = 'trac.db';
if (!file_exists($dbfile)) {
    error_log("'$dbfile' does not exist");
    exit(1);
}
$db = new PDO("sqlite:$dbfile");
$repository_id = getRepositoryId();
$scm_name = 'svn';

echo "Repository Id: $repository_id\n";
processCommits($repository_id);
