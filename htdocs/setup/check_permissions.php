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

use Eventum\Config\Paths;

require_once __DIR__ . '/../../init.php';

clearstatcache();

echo '<html>
<head>
<link rel="stylesheet" href="../css/style.css" type="text/css">
</head>
<body>';

$setupFile = Setup::getSetupFile();
echo "<p class=\"default\">This script checks your eventum directory for permission problems. Since different hosts will have
permissions setup differently this script cannot automatically fix permission problems.</p>
<p class=\"default\">As a general rule, your webserver should be running as 'nobody' (a user with few permissions)
and your files should not be writable from the web. Only your logs (" . Paths::APP_LOG_PATH . ') and setup (' .
    $setupFile . ') files need to be writable by the web server.</p>

<p>The commands listed in the comments are only examples and may not work for every installation.';

echo '<table cellpadding="3">
        <tr class="default">
          <th style="border: 1px solid black;">Title</th>
          <th style="border: 1px solid black;">Path</th>
          <th style="border: 1px solid black;">Is Readable?</th>
          <th style="border: 1px solid black;">Is Writeable?</th>
          <th style="border: 1px solid black;">Comments</th>
        </tr>';

// check if base directory is writeable
check_file('Base Directory', Paths::APP_PATH, 'Base directory and sub directories should be read only by your web server.
                <em>chmod -R a-w ' . Paths::APP_PATH . '</em>', 'r');

check_file('Log Directory', Paths::APP_LOG_PATH, 'Log directory should be writable by your webserver. However, your web
    server should <b>NOT</b> be able to read this directory to prevent outsiders from viewing your logs.
                <em>chmod -R a-r ' . Paths::APP_LOG_PATH . '</em>', 'w');

check_file('Setup File', $setupFile, "The setup file should be both readable and writable from your web server.
    The setup file is used to store general settings.<br /><b>Note:</b> Once you have eventum configured, you can
    mark this file as 'read only' if you want.", 'rw');

echo '</table>
<p class="default">Once you are done setting permissions, you should remove or restrict access to this setup directory (' . APP_PATH . '/setup/).</p>
</body>
</html>';

function check_file($title, $path, $comment, $desired_permission): void
{
    $readable = is_readable($path);
    $writeable = is_writable($path);

    $needs_attention = false;
    if (($desired_permission === 'r') && (($writeable === true) || ($readable === false))) {
        $needs_attention = true;
    } elseif (($desired_permission === 'w') && (($writeable === false) || ($readable === true))) {
        $needs_attention = true;
    } elseif (($desired_permission === 'rw') && (($writeable === false) || ($readable === false))) {
        $needs_attention = true;
    }

    if ($needs_attention) {
        $color = 'red';
    } else {
        $color = 'green';
    }

    echo "<tr class=\"default\">
            <td style=\"border: 1px solid black;\">$title</td>
            <td style=\"border: 1px solid black;\">$path</td>
            <td align=\"center\" style=\"border: 1px solid black;\">" . ($readable === true ? 'yes' : 'no') . '</td>
            <td align="center" style="border: 1px solid black;">' . ($writeable === true ? 'yes' : 'no') . "</td>
            <td style=\"border: 1px solid black;\"><span style=\"color: $color\">$comment</span></td>
          </tr>";
}
