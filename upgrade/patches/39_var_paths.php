<?php

/*
 * migrate pids and locks to new location
 *
 * https://github.com/eventum/eventum/pull/81
 */

$old_dir = APP_PATH . '/locks';
$new_dir = APP_LOCKS_PATH;

if (realpath($old_dir) == realpath($new_dir)) {
    // user has decided to use old structure, nothing to migrate
    return;
}

if (!Misc::isWritableDirectory($new_dir)) {
    // shouldn't happen
    throw new UnexpectedValueException('APP_LOCKS_PATH is not writable');
}

$files = Misc::getFileList($old_dir);
if (!$files) {
    // nothing to migrate
    return;
}

/** @var Closure $log */

$count = count($files);
$log("Migrating $count files from $old_dir to $new_dir");
foreach ($files as $file) {
    $old_file = "$old_dir/$file";
    $new_file = "$new_dir/$file";
    $res = copy($old_file, $new_file);
    if ($res == false) {
        throw new BadMethodCallException("Could not copy $file");
    }
    // preserve timestamp
    touch($new_file, filemtime($old_file));
}
