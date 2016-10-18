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

namespace Eventum\Model\Entity;

use Date_Helper;
use DateTime;
use DB_Helper;
use LogicException;

abstract class BaseModel
{
    public static function create()
    {
        return new static();
    }

    /**
     * @param int $value
     * @return $this
     */
    protected function setId($value)
    {
        throw new LogicException('Entity class must implement setId');
    }

    /**
     * Save current object, not capable of anything smarter
     *
     * @return int
     */
    public function save()
    {
        $tableName = $this->getTableName();
        $params = $this->getData();

        $stmt = "INSERT INTO {$tableName} SET " . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);
        $id = DB_Helper::get_last_insert_id();

        // set primary key to the saved value
        $this->setId($id);

        return $id;
    }

    protected function findAllByConditions($where, $limit = null, $order = null, $conditionJoin = ' AND ')
    {
        $tableName = $this->getTableName();
        $stmt = "SELECT * FROM {$tableName} WHERE ";
        $params = [];
        $conditions = [];
        foreach ($where as $col => $val) {
            $conditions[] = "$col=?";
            $params[] = $val;
        }
        $stmt .= implode($conditionJoin, $conditions);
        if ($order) {
            $stmt .= " ORDER BY $order";
        }
        if ($limit) {
            $stmt .= " LIMIT $limit";
        }
        $db = DB_Helper::getInstance();
        $rows = $db->getAll($stmt, $params);

        // return null for no rows
        if (!$rows) {
            return null;
        }

        // map to model
        $res = [];
        foreach ($rows as $row) {
            $o = new static();
            foreach ($row as $field => $value) {
                if (substr($field, -5) == '_date') {
                    $value = Date_Helper::getDateTime($value);
                }
                $o->{$field} = $value;
            }
            $res[] = $o;
        }

        return $res;
    }

    protected function deleteByQuery($query, $params)
    {
        $tableName = $this->getTableName();
        $stmt = "DELETE FROM {$tableName} WHERE " . $query;
        $db = DB_Helper::getInstance();
        $db->query($stmt, $params);
    }

    /**
     * Get db_field => value pairs of current object
     *
     * @return array
     */
    private function getData()
    {
        $data = get_object_vars($this);

        // Convert DateTime objects
        foreach ($data as &$value) {
            if ($value instanceof DateTime) {
                $value = Date_Helper::convertDateGMT($value);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Get table name from current class
     *
     * @return string
     */
    private function getTableName()
    {
        $tableName = substr(strrchr(get_class($this), '\\'), 1);
        $f = function ($m) {
            return '_' . strtolower($m[0][0]);
        };
        $tableName = preg_replace_callback('/[A-Z]/', $f, $tableName);
        $tableName = ltrim($tableName, '_');

        return "{{%$tableName}}";
    }
}
