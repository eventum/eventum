<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2010 Bryan Alsdorf                                     |
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+


class Product
{
    public static function getList($include_removed=null)
    {
        $data = array();
        $sql = "SELECT
                    pro_id,
                    pro_title,
                    pro_version_howto,
                    pro_rank,
                    pro_removed
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product";
        if ($include_removed !== null) {
            $sql .= "
                WHERE
                    pro_removed = ?";
            $data[] = $include_removed;
        }
        $sql .= "
                ORDER BY
                    pro_rank";
        $res = DB_Helper::getInstance()->getAll($sql, $data, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
        	Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return array();
        }
        return $res;
    }


    public static function getAssocList($removed=null)
    {
        $list = self::getList($removed);
        $return = array();
        foreach ($list as $product) {
            $return[$product['pro_id']] = $product['pro_title'];
        }
        return $return;
    }


    public static function insert($title, $version_howto, $rank, $removed)
    {
        if ($removed != 1) {
            $removed = 0;
        }
        $data = array($title, $version_howto, $rank, $removed);
        $sql = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product
                SET
                    pro_title = ?,
                    pro_version_howto = ?,
                    pro_rank = ?,
                    pro_removed = ?";
        $res = DB_Helper::getInstance()->query($sql, $data);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return -1;
        }
        return 1;
    }


    public static function update($id, $title, $version_howto, $rank, $removed)
    {
        if ($removed != 1) {
            $removed = 0;
        }
        $data = array($title, $version_howto, $rank, $removed, $id);
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product
                SET
                    pro_title = ?,
                    pro_version_howto = ?,
                    pro_rank = ?,
                    pro_removed = ?
                WHERE
                    pro_id = ?";
        $res = DB_Helper::getInstance()->query($sql, $data);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return -1;
        }
        return 1;
    }


    public static function remove($ids)
    {
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product
                WHERE
                    pro_id IN(" . join(', ', Misc::escapeInteger($ids)) . ")";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
        	Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return -1;
        }
        return 1;
    }


    public static function getDetails($pro_id)
    {
        $sql = "SELECT
                    pro_id,
                    pro_title,
                    pro_version_howto,
                    pro_rank,
                    pro_removed
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product
                WHERE
                    pro_id = ?";
        $res = DB_Helper::getInstance()->getRow($sql, array($pro_id), DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return array();
        }
        return $res;
    }


    public static function getTitle($pro_id)
    {
        $product = self::getDetails($pro_id);
        return $product['pro_title'];
    }


    public static function addIssueProductVersion($issue_id, $pro_id, $version)
    {
        $sql = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_product_version
                SET
                    ipv_iss_id = ?,
                    ipv_pro_id = ?,
                    ipv_version = ?";
        $data = array($issue_id, $pro_id, $version);
        $res = DB_Helper::getInstance()->query($sql, $data);
        if (PEAR::isError($res)) {
        	Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return false;
        }
        return true;
    }


    public static function getProductsByIssue($issue_id)
    {
        $sql = "SELECT
                    ipv_id,
                    pro_id,
                    pro_title as product,
                    ipv_version as version
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_product_version,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "product
                WHERE
                    ipv_pro_id = pro_id AND
                    ipv_iss_id = ?";
        $res = DB_Helper::getInstance()->getAll($sql, array($issue_id), DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
        	Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return array();
        }
        return $res;
    }


    public static function updateProductsByIssue($issue_id, $products, $versions)
    {
        $old = self::getProductsByIssue($issue_id);
        $changes = array();
        foreach ($products as $ipv_id => $pro_id) {
            if ($ipv_id == 0) {
                self::addIssueProductVersion($issue_id, $pro_id, $versions[$ipv_id]);
            } else {
                self::updateProductAndVersion($ipv_id, $pro_id, $versions[$ipv_id]);
            }
        }

        foreach ($old as $row) {
            $ipv_id = $row['ipv_id'];
            if ($row['pro_id'] != $products[$ipv_id]) {
                $changes[] = "Product changed from '" . $row['product'] . "' to '" . Product::getTitle($products[$ipv_id]);
            }
            if ($row['version'] != $versions[$ipv_id]) {
                $changes[] = "Product version changed from '" . $row['version'] . "' to '" . $versions[$ipv_id] . "'";
            }
        }
        return $changes;

    }


    public static function updateProductAndVersion($ipv_id, $pro_id, $version)
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_product_version
                SET
                    ipv_pro_id = ?,
                    ipv_version = ?
                WHERE
                    ipv_id = ?";
        $data = array($pro_id, $version, $ipv_id);
        $res = DB_Helper::getInstance()->query($sql, $data);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()));
            return false;
        }
        return true;

    }
}