<?php
include_once("../config.inc.php");

clearstatcache();

echo "<html>
<head>
<link rel=\"stylesheet\" href=\"/css/other.css\" type=\"text/css\">
</head>
<body>";

echo "<p class=\"default\">This script checks your eventum directory for permission problems. Since different hosts will have
permissions setup differently this script cannot automatically correct fix permission problems.</p>
<p class=\"default\">As a general rule, your webserver should be running as 'nobody', a user with few permissions
and your files should not be writable from the web. Only your logs (" . APP_LOG_PATH . ") and setup (" . 
APP_SETUP_FILE . ") files need to be writeable by the web server.";

echo "<table border=\"1\">
        <tr class=\"default\">
          <th>Title</th>
          <th>Path</th>
          <th>Is Readable?</th>
          <th>Is Writeable?</th>
          <th>Comments</th>
        </tr>";

// check if base directory is writeable
check_file("Base Directory", APP_PATH, "Base directory should be read only by your web server", "r");

check_file("Log Directory", APP_LOG_PATH, "Log directory should be writeable by your webserver. However, your web
    server should <b>NOT</b> be able to read this directory to prevent outsiders from viewing your logs.", "w");

check_file("Setup File", APP_SETUP_FILE, "The setup file should be both readable and writeable from your web server.
    The setup file is used to store general settings in.<br /><b>Note:</b> Once you have eventum configured, you can 
    mark this file as 'read only' if you want.", "rw");

echo "</table>
<p class=\"default\">Once you are done setting permissions, you should remove or restrict access to this setup directory (" . APP_PATH . "setup/).
</body>
</html>";

function check_file($title, $path, $comment, $desired_permission)
{
    $readable = is_readable($path);
    $writeable = is_writable($path);
    
    $needs_attention = false;
    if (($desired_permission == "r") && (($writeable == true) || ($readable == false))) {
        $needs_attention = true;
    } elseif (($desired_permission == "w") && (($writeable == false) || ($readable == true))) {
        $needs_attention = true;
    } elseif (($desired_permission == "rw") && (($writeable == false) || ($readable == false))) {
        $needs_attention = true;
    }
    
        
    if ($needs_attention) {
        $color = "red";
    } else {
        $color = "green";
    }
    
    echo "<tr class=\"default\">
            <td>$title</td>
            <td>$path</td>
            <td align=\"center\">" . ($readable == true ? "yes" : "no") . "</td>
            <td align=\"center\">" . ($writeable == true ? "yes" : "no") . "</td>
            <td><span style=\"color: $color\">$comment</span></td>
          </tr>";
}
?>