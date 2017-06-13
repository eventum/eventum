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
 * that is db patches after 53-55 introduced in eventum 3.0.12
 */

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Model\Entity\Commit;

require __DIR__ . '/../init.php';

# map repo types, everything that is not mapped, is treated as git repo
$repo_types = [
    'default' => 'cvs',
    'svn' => 'svn',
    'cvs' => 'cvs',
];
# git repo name to use after migration
$git_name = 'git';

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

function all_repos()
{
    global $db;

    return $db->getColumn('SELECT DISTINCT com_scm_name FROM {{%commit}}');
}

/**
 * - move com_scm_name as com_project_name
 * - set com_scm_name to $git_name
 * - clear file versions, git doesn't track changes per files [notyet]
 */
function migrate_git_repos()
{
    global $db, $all_repos, $repo_types, $git_name;

    $git_repos = array_filter(
        $all_repos, function ($e) use ($repo_types) {
            return array_key_exists($e, $repo_types) === false;
        }
    );

    foreach ($git_repos as $repo) {
        $commits = $db->getColumn('SELECT com_id FROM {{%commit}} WHERE com_scm_name=?', [$repo]);
        $commits = implode(',', $commits);
        $db->query(
            'UPDATE {{%commit}} SET com_project_name=com_scm_name, com_scm_name=? WHERE com_scm_name=? and com_scm_name!=?',
            [$git_name, $repo, $git_name]
        );
        echo "$repo -> $commits\n";
    }
}

/**
 * Create a routine that, given a set of strings representing directory paths and a single character directory separator, will return a string representing that part of the directory tree that is common to all the directories.
 *
 * Test your routine using the forward slash '/' character as the directory separator and the following three strings as input paths:
 * '/home/user1/tmp/coverage/test'
 * '/home/user1/tmp/covert/operator'
 * '/home/user1/tmp/coven/members'
 * Note: The resultant path should be the valid directory '/home/user1/tmp' and not the longest common string '/home/user1/tmp/cove'.
 * If your language has a routine that performs this function (even if it does not have a changeable separator character), then mention it as part of the task.
 *
 * @param array $dirList
 * @see https://www.rosettacode.org/wiki/Find_common_directory_path#PHP
 * @return string
 */
function find_common_path($dirList)
{
    $arr = [];
    foreach ($dirList as $i => $path) {
        $dirList[$i] = explode('/', $path);
        unset($dirList[$i][0]);

        $arr[$i] = count($dirList[$i]);
    }

    $min = min($arr);

    for ($i = 0; $i < count($dirList); $i++) {
        while (count($dirList[$i]) > $min) {
            array_pop($dirList[$i]);
        }

        $dirList[$i] = '/' . implode('/', $dirList[$i]);
    }

    $dirList = array_unique($dirList);
    while (count($dirList) !== 1) {
        $dirList = array_map('dirname', $dirList);
        $dirList = array_unique($dirList);
    }
    reset($dirList);

    return current($dirList);
}

/**
 * find common root (directory path) of files
 *
 * @param $files
 * @return string
 */
function find_common_root($files)
{
    $dirs = array_map(
        function ($f) {
            $dir = dirname($f);
            // prepend leading slash and avoid duplicate slashes
            return '/' . ltrim($dir, '/');
        }, $files
    );

    $dir = find_common_path($dirs);

    // strip leading slash that we added
    $dir = ltrim($dir, '/');

    return $dir;
}

/**
 * - Find common root of files in changeset, set that as com_project_name
 */
function migrate_svn_repos()
{
    global $db, $all_repos, $repo_types;
    $svn_repos = array_filter(
        $all_repos, function ($e) use ($repo_types) {
            return isset($repo_types[$e]) && $repo_types[$e] == 'svn';
        }
    );
    $list = DB_Helper::buildList($svn_repos);
    $commits = $db->getColumn("select com_id from {{%commit}} where com_scm_name in ($list)", $svn_repos);

    foreach ($commits as $commit) {
        $files = $db->getColumn('SELECT cof_filename FROM {{%commit_file}} WHERE cof_com_id=?', [$commit]);
        $commit_root = find_common_root($files);
        $db->query(
            'UPDATE {{%commit}} SET com_project_name=? WHERE com_id=?',
            [$commit_root, $commit]
        );
        echo "$commit -> $commit_root\n";
    }
}

/**
 * Set usr_id to commits using workflow.
 * This assumes workflow preScmCommit() method sets UserId
 */
function set_commit_users($prj_id = 1)
{
    global $db;

    // needs workflow backend to work
    $backend = Workflow::_getBackend($prj_id);
    if (!$backend) {
        return;
    }

    $commits = $db->getColumn('SELECT com_id FROM {{%commit}} WHERE com_usr_id IS NULL');
    echo count($commits), " commits to check\n";
    $co = Commit::create();
    $cache = [];
    foreach ($commits as $com_id) {
        $commit = $co->findById($com_id);
        $cache_key = $commit->getAuthor();

        if (!isset($cache[$cache_key])) {
            $backend->preScmCommit($prj_id, $commit, null);
            $usr_id = $commit->getUserId();
            if ($usr_id) {
                echo "New mapping [$cache_key]: usr_id=$usr_id\n";
            } else {
                echo "No mapping [$cache_key]\n";
            }
            $cache[$cache_key] = $usr_id ?: false;
        }

        $usr_id = $cache[$cache_key];
        if ($usr_id) {
            //            echo "Updating: #{$commit->getId()} [$cache_key]: usr_id={$usr_id}\n";
            $db->query(
                'UPDATE {{%commit}} SET com_usr_id=? WHERE com_id=?',
                [$usr_id, $commit->getId()]
            );
        }
    }
}

$all_repos = all_repos();

migrate_git_repos();
migrate_svn_repos();
set_commit_users();
