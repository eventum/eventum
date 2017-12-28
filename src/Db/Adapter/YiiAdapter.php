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

namespace Eventum\Db\Adapter;

use Eventum\Db;
use PDO;
use UnexpectedValueException;

/**
 * Class YiiAdapter
 *
 * Proxy PEAR::DB like interface to Yii2
 */
class YiiAdapter extends PdoAdapterBase implements AdapterInterface
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
        $yiiConfig = $this->getYiiConfig($config);

        $this->app = new \yii\web\Application($yiiConfig);
        $this->connection = \Yii::$app->db;
    }

    /**
     * Create config suitable to boot Yii2 Application
     *
     * @param array $config
     * @return array
     */
    private function getYiiConfig($config)
    {
        $yiiConfig = [
            'id' => 'eventum',
            'basePath' => APP_PATH,

            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => $this->getDsn($config),
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'charset' => $this->getCharset(),
                ],
            ],
        ];

        return $yiiConfig;
    }

    public function getAll($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertParams($params, $fetchMode);
        $this->convertFetchMode($fetchMode);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryAll($fetchMode);
    }

    public function fetchAssoc($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_DEFAULT)
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        $flags = PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        if ($fetchMode == AdapterInterface::DB_FETCHMODE_ASSOC) {
            $flags |= PDO::FETCH_ASSOC;
        } elseif ($fetchMode == AdapterInterface::DB_FETCHMODE_DEFAULT) {
            $flags |= PDO::FETCH_NUM;
        } else {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode: ' . $fetchMode);
        }

        return $command->queryAll($flags);
    }

    public function getPair($query, $params = [])
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryAll(PDO::FETCH_KEY_PAIR);
    }

    public function getColumn($query, $params = [])
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        return $command->queryColumn();
    }

    public function getOne($query, $params = [])
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

    public function getRow($query, $params = [], $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
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
        // doesn't do arrays
        if (!is_scalar($str)) {
            return null;
        }

        $str = $this->connection->quoteValue($str);

        if ($str[0] == "'") {
            return substr($str, 1, -1);
        }

        return $str;
    }

    public function query($query, $params = [])
    {
        $this->convertParams($params);
        $command = $this->connection->createCommand($query, $params);

        $command->execute();

        return true;
    }

    public function quoteIdentifier($str)
    {
        return $this->connection->quoteColumnName($str);
    }

    /**
     * PEAR DB allowed to switch $params and $fetchmode
     *
     * To avoid PDO error "Invalid parameter number: Columns/Parameters are 1-based"
     * Shift params to be 1-based.
     * @param int $fetchmode
     */
    private function convertParams(&$params, &$fetchmode = null)
    {
        // compat check, the params and fetchmode parameters used to have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = AdapterInterface::DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = [];
            }
        }

        // can't use isset() as 0 may be null
        if (array_key_exists(0, $params)) {
            array_unshift($params, false);
            unset($params[0]);
        }
    }
}
