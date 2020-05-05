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

namespace Eventum\Config;

use DB_Helper;
use Setup;

class SphinxConfig
{
    /** @var string */
    public $host;
    /** @var int */
    public $port;
    /** @var string */
    public $log_path;
    /** @var string */
    public $run_path;
    /** @var string */
    public $data_path;
    /** @var string */
    public $sql_sock_enabled;
    /** @var string */
    public $sql_host;
    /** @var string */
    public $sql_sock;
    /** @var int */
    public $sql_port;
    /** @var string */
    public $sql_username;
    /** @var string */
    public $sql_password;
    /** @var string */
    public $sql_database;

    public function __construct()
    {
        $setup = Setup::get();
        $this->host = $setup['sphinx_searchd_host'];
        $this->port = $setup['sphinx_searchd_port'];
        $this->log_path = $setup['sphinx_log_path'];
        $this->run_path = $setup['sphinx_run_path'];
        $this->data_path = $setup['sphinx_data_path'];

        $dbconfig = DB_Helper::getConfig();

        // support localhost:/path/to/socket.sock syntax in db host
        $this->sql_sock_enabled = '# ';
        $this->sql_host = $dbconfig['hostname'];
        $this->sql_sock = '';

        $parts = explode(':', $this->sql_host, 2);
        if (count($parts) >= 2 && list($host, $socket) = $parts) {
            $this->sql_sock_enabled = '';
            $this->sql_host = $host;
            $this->sql_sock = $socket;
        }

        $this->sql_port = $dbconfig['port'];
        $this->sql_username = $dbconfig['username'];
        $this->sql_password = $dbconfig['password'];
        $this->sql_database = $dbconfig['database'];
    }
}
