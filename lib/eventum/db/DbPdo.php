<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+

class DbPdo extends DbBasePdo implements DbInterface
{
    /** @var PDO */
    private $db;

    /**
     * @param $config
     */
    public function __construct(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

        global $debugbar;
        if ($debugbar) {
            $pdo = new DebugBar\DataCollector\PDO\TraceablePDO($pdo);
            $debugbar->addCollector(new DebugBar\DataCollector\PDO\PDOCollector($pdo));
        }

        $this->db = $pdo;
        $this->tablePrefix = $config['table_prefix'];
    }

    public function getAll($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertFetchMode($fetchmode);

        return $this->fetchAll($query, $params, $fetchmode);
    }

    public function fetchAssoc($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT)
    {
        $flags = PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        if ($fetchmode == DbInterface::DB_FETCHMODE_ASSOC) {
            $flags |= PDO::FETCH_ASSOC;
        } elseif ($fetchmode == DbInterface::DB_FETCHMODE_DEFAULT) {
            $flags |= PDO::FETCH_NUM;
        } else {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode: ' . $fetchmode);
        }

        return $this->fetchAll($query, $params, $flags);
    }

    public function getPair($query, $params = array())
    {
        return $this->fetchAll($query, $params, PDO::FETCH_KEY_PAIR);
    }

    public function getColumn($query, $params = array())
    {
        return $this->fetchAll($query, $params, PDO::FETCH_COLUMN);
    }

    public function getOne($query, $params = array())
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $res = $stmt->fetchColumn();

        // emulate empty result
        if ($res === false) {
            return null;
        }

        return $res;
    }

    public function getRow($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $this->convertFetchMode($fetchmode);
        return $stmt->fetch($fetchmode);
    }

    /**
     * @deprecated this is broken by design, should use parameters instead
     * @param string $str
     * @return string
     */
    public function escapeSimple($str)
    {
        // doesn't do arrays
        if (!is_scalar($str)) {
            return null;
        }

        $str = $this->db->quote($str);

        if ($str[0] == "'") {
            return substr($str, 1, -1);
        }

        return $str;
    }

    public function query($query, $params = array())
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return true;
    }

    public function quoteIdentifier($str)
    {
        return "`" . str_replace("`", "``", $str) . "`";
    }

    /**
     * Common method for API
     */
    private function fetchAll($query, $params, $fetchmode)
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll($fetchmode);
    }

    private function quoteSql($sql)
    {
        return DB_Helper::quoteTableName($this, $this->tablePrefix, $sql);
    }
}
