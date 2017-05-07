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

namespace Eventum\Db;

use Closure;
use DB_Helper;
use Eventum\Db\Adapter\AdapterInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class handling database migrations
 */
class Migrate
{
    /** @var AdapterInterface */
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

    /** @var Closure */
    private $logger;

    /**
     * @param string $schema_dir
     */
    public function __construct($schema_dir)
    {
        $this->db = DB_Helper::getInstance();
        $this->dir = $schema_dir;
        $this->config = DB_Helper::getConfig();
        $this->table_prefix = $this->config['table_prefix'];
        $this->logger = function ($e) {
            echo $e, "\n";
        };
    }

    /**
     * @param Closure $e
     */
    public function setLogger($e)
    {
        if (!is_callable($e)) {
            throw new InvalidArgumentException('Passed argument is not callable');
        }
        $this->logger = $e;
    }

    /**
     * Log a message
     *
     * @param string $e
     */
    private function log($e)
    {
        $logger = $this->logger;
        $logger($e);
    }

    /**
     * @param int $patch a specific patch to run, otherwise run any patch that is not applied yet
     */
    public function patch_database($patch = null)
    {
        // sanity check. check that the version table exists.
        if (!$this->hasVersionTable()) {
            $this->init_database();
        }

        $patches = $this->applied_patches();
        $files = $this->read_patches("{$this->dir}/patches");

        if ($patch) {
            $this->apply_patch($files, $patch);

            return;
        }

        $addCount = 0;
        $maxpatch = max(array_keys($files)) ?: 0;
        foreach ($files as $number => $file) {
            if (isset($patches[$number])) {
                // patch already applied. next please
                continue;
            }

            $basename = basename($file);
            $this->log("* Applying patch: $number ($basename)");
            $this->exec_sql_file($file);
            $this->add_version($number);
            $addCount++;

            // rescan patches, in case the patch modified version table
            $patches = $this->applied_patches();
        }

        if ($addCount == 0) {
            $this->log("* Your database is already up-to-date. Version $maxpatch");
        } else {
            $this->log("* Your database is now up-to-date. Version $maxpatch");
        }
    }

    private function init_database()
    {
        $schemafile = 'schema.sql';
        $this->log("* Creating database: $schemafile ");
        $this->exec_sql_file("{$this->dir}/{$schemafile}");
    }

    /**
     * apply specific patch
     *
     * could be used to re-run certain patch
     * does not update version table
     *
     * @param array $files patch number to filename mapping
     * @param int $patch patch number to apply
     */
    private function apply_patch($files, $patch)
    {
        if (!isset($files[$patch])) {
            throw new InvalidArgumentException("No such patch: $patch");
        }
        $file = $files[$patch];
        $basename = basename($file);
        $this->log("* Applying patch: $patch ($basename)");
        $this->exec_sql_file($file);
    }

    private function exec_sql_file($input_file)
    {
        if (!file_exists($input_file) && !is_readable($input_file)) {
            throw new RuntimeException("Can't read file: $input_file");
        }

        // use *.php for complex updates
        if (substr($input_file, -4) == '.php') {
            self::include_file($input_file, $this->db, $this->config, $this->logger);

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
     * Isolate, to prevent access to class properties.
     * PHP patches have access to $db and $dbconfig variables,
     * and if they wish to echo something, should use $log() closure.
     *
     * @param string $file
     * @param AdapterInterface $db
     * @param array $dbconfig
     * @param Closure $log
     */
    private static function include_file($file, $db, $dbconfig, $log)
    {
        require $file;
    }

    /**
     * Scan filesystem for all patches
     *
     * @param string $update_path
     * @return array patches indexed by their number
     */
    private function read_patches($update_path)
    {
        $handle = opendir($update_path);
        if (!$handle) {
            throw new RuntimeException("Could not read: $update_path");
        }
        while (false !== ($file = readdir($handle))) {
            $number = substr($file, 0, strpos($file, '_'));
            if (in_array(substr($file, -4), ['.sql', '.php']) && is_numeric($number)) {
                $files[(int)$number] = "$update_path/$file";
            }
        }
        closedir($handle);
        ksort($files);

        return $files;
    }

    /**
     * Return true if version table exists
     *
     * @return bool
     */
    private function hasVersionTable()
    {
        $res = $this->db->getOne("SHOW TABLES LIKE '{$this->table_prefix}version'");

        return $res !== null;
    }

    /**
     * Return true if version table is log based
     */
    private function hasVersionLog()
    {
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

        $patches = [];

        // check for old table format
        if (!$this->hasVersionLog()) {
            // old table, return versions based ver_version value
            $last_patch = $this->db->getOne('SELECT ver_version FROM {{%version}}');

            for ($i = 1; $i <= $last_patch; $i++) {
                $patches[$i] = time();
            }

            return $patches;
        }

        $patches = $this->db->getPair('SELECT ver_version,ver_timestamp FROM {{%version}} ORDER BY 1');

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
