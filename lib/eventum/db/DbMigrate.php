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

    /** @var array */
    private $config;

    /**
     * Top directory where SQL schema and patches are
     *
     * @var string
     */
    private $dir;

    /** @var string */
    private $table_prefix;

    public function __construct($schema_dir)
    {
        $this->db = DB_Helper::getInstance();
        $this->dir = $schema_dir;
        $this->config = DB_Helper::getConfig();
        $this->table_prefix = $this->config['table_prefix'];
    }

    public function patch_database()
    {
        // sanity check. check that the version table exists.
        if (!$this->hasVersionTable()) {
            $this->init_database();
        }

        $patches = $this->applied_patches();
        $files = $this->read_patches("{$this->dir}/patches");

        $addCount = 0;
        $maxpatch = max(array_keys($files)) ?: 0;
        foreach ($files as $number => $file) {
            if (isset($patches[$number])) {
                // patch already applied. next please
                continue;
            }

            echo '* Applying patch: ', $number, ' (', basename($file), ")\n";
            $this->exec_sql_file($file);
            $this->add_version($number);
            $addCount++;

            // rescan patches, in case the patch modified version table
            $patches = $this->applied_patches();
        }

        if ($addCount == 0) {
            echo "* Your database is already up-to-date. Version $maxpatch\n";
        } else {
            echo "* Your database is now up-to-date. Version $maxpatch\n";
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
            self::include_file($input_file, $this->db, $this->config);
            return;
        }

        $queries = explode(';', file_get_contents($input_file));
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->db->query($query);
            }
        }
    }

    /**
     * isolate, prevent access to class properties.
     * php patches have access to $db and $dbconfig variables.
     *
     * @param string $file
     * @param DbInterface $db
     * @param array $dbconfig
     */
    private static function include_file($file, $db, $dbconfig)
    {
        require $file;
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
                $files[(int)$number] = "$update_path/$file";
            }
        }
        closedir($handle);
        ksort($files);

        return $files;
    }

    /**
     * Return true if version table exists
     * @return bool
     */
    private function hasVersionTable() {
        $res = $this->db->getOne("SHOW TABLES LIKE '{$this->table_prefix}version'");
        return $res !== null;
    }

    /**
     * Return true if versio table is log based
     */
    private function hasVersionLog() {
        // check if ver_patch column exists
        $res = $this->db->getOne("SHOW FIELDS FROM {{%version}} LIKE 'ver_timestamp'");
        return $res !== null;
    }

    /**
     * get applied patches from version table
     * returns null if version table is not found
     */
    private function applied_patches()
    {
        if (!$this->hasVersionTable()) {
            return null;
        }

        $patches = array();

        // check for old table format
        if (!$this->hasVersionLog()) {
            // old table, return versions based ver_version value
            $last_patch = $this->db->getOne('SELECT ver_version FROM {{%version}}');

            for ($i = 1; $i <= $last_patch; $i++) {
                $patches[$i] = time();
            }
            return $patches;
        }

        $patches = $this->db->getPair("SELECT ver_version,ver_timestamp FROM {{%version}} ORDER BY 1");
        return $patches;
    }

    private function add_version($number)
    {
        if ($this->hasVersionLog()) {
            $this->db->query("INSERT INTO {{%version}} SET ver_version=$number, ver_timestamp=NOW()");
        } else {
            $this->db->query("UPDATE {{%version}} SET ver_version=$number");
        }
    }

}
