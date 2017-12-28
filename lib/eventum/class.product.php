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

use Eventum\Db\DatabaseException;

class Product
{
    public static function getList($include_removed = null)
    {
        $params = [];
        $sql = 'SELECT
                    pro_id,
                    pro_title,
                    pro_version_howto,
                    pro_rank,
                    pro_removed,
                    pro_email
                FROM
                    `product`';
        if ($include_removed !== null) {
            $sql .= '
                WHERE
                    pro_removed = ?';
            $params[] = $include_removed;
        }
        $sql .= '
                ORDER BY
                    pro_rank';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    public static function getAssocList($removed = null)
    {
        $list = self::getList($removed);
        $return = [];
        foreach ($list as $product) {
            $return[$product['pro_id']] = $product['pro_title'];
        }

        return $return;
    }

    public static function insert($title, $version_howto, $rank, $removed, $email)
    {
        if ($removed != 1) {
            $removed = 0;
        }
        $params = [$title, $version_howto, $rank, $removed, $email];
        $sql = 'INSERT INTO
                    `product`
                SET
                    pro_title = ?,
                    pro_version_howto = ?,
                    pro_rank = ?,
                    pro_removed = ?,
                    pro_email = ?';
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    public static function update($id, $title, $version_howto, $rank, $removed, $email)
    {
        if ($removed != 1) {
            $removed = 0;
        }
        $params = [$title, $version_howto, $rank, $removed, $email, $id];
        $sql = 'UPDATE
                    `product`
                SET
                    pro_title = ?,
                    pro_version_howto = ?,
                    pro_rank = ?,
                    pro_removed = ?,
                    pro_email = ?
                WHERE
                    pro_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    public static function remove($ids)
    {
        $sql = 'DELETE FROM
                    `product`
                WHERE
                    pro_id IN (' . DB_Helper::buildList($ids) . ')';

        try {
            DB_Helper::getInstance()->query($sql, $ids);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    public static function getDetails($pro_id)
    {
        $sql = 'SELECT
                    pro_id,
                    pro_title,
                    pro_version_howto,
                    pro_rank,
                    pro_removed,
                    pro_email
                FROM
                    `product`
                WHERE
                    pro_id = ?';

        try {
            $res = DB_Helper::getInstance()->getRow($sql, [$pro_id]);
        } catch (DatabaseException $e) {
            return [];
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
        if ($pro_id == '-1') {
            return true;
        }

        $sql = 'INSERT INTO
                    `issue_product_version`
                SET
                    ipv_iss_id = ?,
                    ipv_pro_id = ?,
                    ipv_version = ?';
        $params = [$issue_id, $pro_id, $version];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    public static function getProductsByIssue($issue_id)
    {
        $sql = 'SELECT
                    ipv_id,
                    pro_id,
                    pro_title as product,
                    ipv_version as version,
                    pro_email
                FROM
                    `issue_product_version`,
                    `product`
                WHERE
                    ipv_pro_id = pro_id AND
                    ipv_iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    public static function updateProductsByIssue($issue_id, $products, $versions)
    {
        $old = self::getProductsByIssue($issue_id);
        $changes = [];
        foreach ($products as $ipv_id => $pro_id) {
            if ($ipv_id == 0) {
                $old[] = ['ipv_id' => 0, 'pro_id' => '', 'product' => '', 'version' => ''];
                self::addIssueProductVersion($issue_id, $pro_id, $versions[$ipv_id]);
            } else {
                self::updateProductAndVersion($ipv_id, $pro_id, $versions[$ipv_id]);
            }
        }

        foreach ($old as $row) {
            $ipv_id = $row['ipv_id'];
            if ($row['pro_id'] != $products[$ipv_id]) {
                $changes[] = "Product changed from '" . $row['product'] . "' to '" . self::getTitle($products[$ipv_id]);
            }
            if ($row['version'] != $versions[$ipv_id]) {
                $changes[] = "Product version changed from '" . $row['version'] . "' to '" . $versions[$ipv_id] . "'";
            }
        }

        return $changes;
    }

    public static function updateProductAndVersion($ipv_id, $pro_id, $version)
    {
        if ($pro_id == -1) {
            $sql = 'DELETE FROM
                        `issue_product_version`
                    WHERE
                        ipv_id = ?';
            $params = [$ipv_id];
        } else {
            $sql = 'UPDATE
                        `issue_product_version`
                    SET
                        ipv_pro_id = ?,
                        ipv_version = ?
                    WHERE
                        ipv_id = ?';
            $params = [$pro_id, $version, $ipv_id];
        }
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }
}
