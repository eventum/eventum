#!/usr/bin/php
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

/*
 * This script is able to migrate scm repo commits to newer structure.
 *
 * for git repos:
 * - move com_scm_name as cof_project_name
 * - clear file versions, git doesn't track changes per files [notyet]
 * - fill added, removed, modified flag [notyet]
 */

use Eventum\Db\Adapter\AdapterInterface;

require __DIR__ . '/../init.php';

# map repo types, everything that is not mapped, is treated as git repo
$repo_types = array(
    'default' => 'cvs',
    'svn' => 'svn',
    'cvs' => 'cvs',
);

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

function all_repos()
{
    global $db;

    return $db->getColumn("SELECT DISTINCT com_scm_name FROM {{%commit}}");
}

function migrate_git_repos($repos)
{
    global $db;

    foreach ($repos as $repo) {
        $commits = $db->getColumn("select com_id from {{%commit}} where com_scm_name=?", array($repo));
        $commits = join(',', $commits);
        $db->query("update {{%commit_file}} set cof_project_name=? where cof_com_id in ($commits)", array($repo));
        echo "$repo -> $commits\n";
    }
}

$all_repos = all_repos();

$git_repos = array_filter(
    $all_repos, function ($e) use ($repo_types) {
        return array_key_exists($e, $repo_types) === false;
    }
);

migrate_git_repos($git_repos);
