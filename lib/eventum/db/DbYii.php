<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2015 Eventum Team.                                |
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

require_once 'DB.php';

/**
 * Class DbYii
 *
 * Proxy PEAR::DB like interface to Yii2
 */
class DbYii implements DbInterface
{
    /**
     * @var \yii\db\Connection
     */
    private $connection;

    /**
     * @param $config
     */
    public function __construct(array $config)
    {
        define('YII_ENABLE_EXCEPTION_HANDLER', false);
        define('YII_ENABLE_ERROR_HANDLER', false);

        /** @noinspection PhpIncludeInspection */
        require_once APP_PATH . '/vendor/yiisoft/yii2/Yii.php';
        $yiiConfig = self::getYiiConfig($config);

        $this->app = new \yii\web\Application($yiiConfig);
        $this->connection = \Yii::$app->db;
    }

    /**
     * Create config suitable to boot Yii2 Application
     *
     * @param array $config
     * @return array
     */
    private static function getYiiConfig($config)
    {
        $dsn = "{$config['driver']}:host={$config['hostname']};dbname={$config['database']}";

        // no dash variant listed, blindly reap "UTF-8" to "UTF8"
        // http://dev.mysql.com/doc/refman/5.0/en/charset-charsets.html
        $charset = strtolower(str_replace('-', '', APP_CHARSET));

        $yiiConfig = array(
            'id'         => 'eventum',
            'basePath'   => APP_PATH,

            'components' => array(
                'db' => array(
                    'class'       => 'yii\db\Connection',
                    'dsn'         => $dsn,
                    'username'    => $config['username'],
                    'password'    => $config['password'],
                    'charset'     => $charset,

                    'tablePrefix' => $config['table_prefix'],
                ),
            ),
        );

        return $yiiConfig;
    }

    public function getAll($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertParams($params, $fetchmode);
        $this->convertFetchMode($fetchmode);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryAll($fetchmode);
    }

    /**
     * @deprecated use getPair where possible
     */
    public function getAssoc(
        $query, $force_array = false, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT, $group = false
    ) {
        if (is_array($force_array)) {
            throw new LogicException('force_array passed as array, did you mean fetchPair or forgot extra arg?');
        }
        if (!$force_array && $fetchmode == DbInterface::DB_FETCHMODE_DEFAULT) {
            return $this->getPair($query, $params);
        }

//        if ($force_array) {
//            var_dump($force_array, $fetchmode == DB_FETCHMODE_ASSOC);
//            throw new UnexpectedValueException(__FUNCTION__ . " unsupported force array");
//        }

        if ($fetchmode != DbInterface::DB_FETCHMODE_ASSOC) {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode');
        }

        if ($group !== false) {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported group mode');
        }

        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    public function fetchAssoc($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT)
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        $flags = PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        if ($fetchmode == DbInterface::DB_FETCHMODE_ASSOC) {
            $flags |= PDO::FETCH_ASSOC;
        } elseif ($fetchmode == DbInterface::DB_FETCHMODE_DEFAULT) {
            $flags |= PDO::FETCH_NUM;
        } else {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode: '. $fetchmode);
        }

        return $command->queryAll($flags);
    }

    public function getPair($query, $params = array())
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @deprecated use getColumn instead
     */
    public function getCol($query, $col = 0, $params = array())
    {
        if (is_array($col)) {
            throw new LogicException('col passed as array, did you mean to use getColumn?');
        }

        if ($col !== 0) {
            throw new UnexpectedValueException(__FUNCTION__ . ' - col != 0 not implemented');
        }

        return $this->getColumn($query, $params);
    }

    public function getColumn($query, $params = array())
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryColumn();
    }

    public function getOne($query, $params = array())
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);
        $res = $command->queryScalar();
        // emulate empty result
        if ($res === false) {
            return null;
        }

        return $res;
    }

    public function getRow($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertParams($params, $fetchmode);
        $this->convertFetchMode($fetchmode);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryOne($fetchmode);
    }

    /**
     * @deprecated this is broken by design, should use parameters instead
     * @param string $str
     * @return string
     */
    public function escapeSimple($str)
    {
        $str = $this->connection->quoteValue($str);

        if ($str[0] == "'") {
            return substr($str, 1, -1);
        }

        return $str;
    }

    public function query($query, $params = array())
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->execute();
    }

    public function quoteIdentifier($str)
    {
        return $this->connection->quoteColumnName($str);
    }

    public function affectedRows()
    {
        throw new RuntimeException(__FUNCTION__.' not implemented');
    }

    /**
     * PEAR DB allowed to switch $params and $fetchmode
     *
     * To avoid PDO error "Invalid parameter number: Columns/Parameters are 1-based"
     * Shift params to be 1-based.
     */
    private function convertParams(&$params, &$fetchmode = null)
    {
        // compat check, the params and fetchmode parameters used to have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = DbInterface::DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = array();
            }
        }

        // can't use isset() as 0 may be null
        if (array_key_exists(0, $params)) {
            array_unshift($params, false);
            unset($params[0]);
        }
    }

    private function convertFetchMode(&$fetchmode)
    {
        switch ($fetchmode) {
            case DbInterface::DB_FETCHMODE_ASSOC:
                $fetchmode = PDO::FETCH_ASSOC;
                break;

            case DbInterface::DB_FETCHMODE_DEFAULT:
                $fetchmode = PDO::FETCH_NUM;
                break;

            default:
                throw new UnexpectedValueException('Unsupported fetchmode');
        }
    }
}
