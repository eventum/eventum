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

namespace Eventum\Controller\Helper;

use DB_Helper;
use Eventum\Db\Adapter\AdapterInterface;

class DbHelper implements AdapterInterface
{
    /** @var AdapterInterface */
    private $db;

    public function __construct()
    {
        $this->db = DB_Helper::getInstance();
    }

    public function escapeSimple($str): string
    {
        return $this->db->escapeSimple($str);
    }

    public function quoteIdentifier($str): string
    {
        return $this->db->quoteIdentifier($str);
    }

    public function getAll($query, $params = [], $fetchMode = self::DB_FETCHMODE_ASSOC)
    {
        return $this->db->getAll($query, $params, $fetchMode);
    }

    public function fetchAssoc($query, $params = [], $fetchMode = self::DB_FETCHMODE_DEFAULT)
    {
        return $this->db->fetchAssoc($query, $params, $fetchMode);
    }

    public function getColumn($query, $params = [])
    {
        return $this->db->fetchAssoc($query, $params);
    }

    public function getOne($query, $params = [])
    {
        return $this->db->fetchAssoc($query, $params);
    }

    public function getPair($query, $params = [])
    {
        return $this->db->fetchAssoc($query, $params);
    }

    public function getRow($query, $params = [], $fetchMode = self::DB_FETCHMODE_ASSOC)
    {
        return $this->db->fetchAssoc($query, $params, $fetchMode);
    }

    public function query($query, $params = [])
    {
        return $this->db->query($query, $params);
    }
}
