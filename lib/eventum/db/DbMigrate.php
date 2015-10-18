<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008-2015 Eventum Team.                                |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Class handling database migrations
 */
class DbMigrate
{
    /** @var DbInterface */
    private $db;

    /**
     * Top directory where SQL schema and patches are
     * @var string
     */
    private $dir;

    /** @var string */
    private $table_prefix;

    public function __construct($schema_dir)
    {
        $this->db = DB_Helper::getInstance();
        $this->dir = $schema_dir;

        $config = DB_Helper::getConfig();
        $this->table_prefix = $config['table_prefix'];
    }

    public function patch_database()
    {
        // sanity check. check that the version table exists.
        $has_table = $this->db->getOne("SHOW TABLES LIKE '{$this->table_prefix}version'");
        if (!$has_table) {
            $this->init_database();
        }

        $last_patch = $this->db->getOne('SELECT ver_version FROM {{%version}}');
        if (!isset($last_patch)) {
            // insert initial value
            $this->db->query('INSERT INTO {{%version}} SET ver_version=0');
            $last_patch = 0;
        }

        $files = $this->read_patches("{$this->dir}/patches");

        $addCount = 0;
        foreach ($files as $number => $file) {
            if ($number > $last_patch) {
                echo '* Applying patch: ', $number, ' (', basename($file), ")\n";
                $this->exec_sql_file($file);
                $this->db->query("UPDATE {{%version}} SET ver_version=$number");
                $addCount++;
            }
        }

        $version = max(array_keys($files));
        if ($addCount == 0) {
            echo "* Your database is already up-to-date. Version $version\n";
        } else {
            echo "* Your database is now up-to-date. Updated from $last_patch to $version\n";
        }
    }

    private function init_database()
    {
        $file = "{$this->dir}/schema.sql";
        echo '* Creating database: ', basename($file), "\n";
        $this->exec_sql_file($file);
    }

    private function exec_sql_file($input_file)
    {
        if (!file_exists($input_file) && !is_readable($input_file)) {
            throw new RuntimeException("Can't read file: $input_file");
        }

        // use *.php for complex updates
        if (substr($input_file, -4) == '.php') {
            $queries = array();
            require $input_file;
        } else {
            $queries = explode(';', file_get_contents($input_file));
        }

        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->db->query($query);
            }
        }
    }

    private function read_patches($update_path)
    {
        $handle = opendir($update_path);
        if (!$handle) {
            throw new RuntimeException("Could not read: $update_path");
        }
        while (false !== ($file = readdir($handle))) {
            $number = substr($file, 0, strpos($file, '_'));
            if (in_array(substr($file, -4), array('.sql', '.php')) && is_numeric($number)) {
                $files[(int) $number] = trim($update_path) . (substr(trim($update_path), -1) == '/' ? '' : '/') . $file;
            }
        }
        closedir($handle);
        ksort($files);

        return $files;
    }
}
