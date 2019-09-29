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

namespace Eventum\Setup;

use DB_Helper;
use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;
use Eventum\Db\Table;
use Phinx\Console\PhinxApplication;
use RuntimeException;
use Setup;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseSetup
{
    /** @var AdapterInterface */
    private $conn;

    private const ERR_DB_NOT_FOUND = 'db_not_found';
    private const ERR_DB_USER_NOT_FOUND = 'db_user_not_found';
    private const ERR_DB_CREATE_ACCESS_FAILURE = 'db_create_access';
    private const ERR_DB_DROP_ACCESS_FAILURE = 'db_drop_access';
    private const ERR_DB_PHINX_FAILURE = 'db_phinx_failure';

    public function __construct(array $dsn)
    {
        Setup::save(['database' => $dsn]);

        $this->conn = $this->getDb();
    }

    /**
     * Check the CREATE and DROP privileges by trying to create and drop a test table.
     *
     * @param string $db_name
     * @throws RuntimeException
     */
    private function checkDatabaseAccess(string $db_name): void
    {
        // check if we can use the database
        try {
            $this->conn->query("USE `{$db_name}`");
        } catch (DatabaseException $e) {
            throw new RuntimeException($e->getMessage());
        }

        $table_list = $this->getTableList();
        if (!in_array('eventum_test', $table_list, true)) {
            try {
                $this->conn->query('CREATE TABLE `eventum_test` (test CHAR(1))');
            } catch (DatabaseException $e) {
                $message = $e->getMessage();
                if (stripos($message, 'Access denied') !== false) {
                    throw new RuntimeException(self::ERR_DB_CREATE_ACCESS_FAILURE);
                }

                throw new RuntimeException($message);
            }
        }
        try {
            $this->conn->query('DROP TABLE eventum_test');
        } catch (DatabaseException $e) {
            $message = $e->getMessage();
            if (stripos($message, 'Access denied') !== false) {
                throw new RuntimeException(self::ERR_DB_DROP_ACCESS_FAILURE);
            }

            throw new RuntimeException($message);
        }
    }

    /**
     * Init database with with upgrade tool.
     * IMPORTANT: this method changes current dir.
     *
     * @throws SetupException
     * @return string output from upgrade script
     */
    private function migrateDatabase(): string
    {
        // run phinx based updater
        chdir(__DIR__ . '/../..');

        // emulate running "migrate" command
        $input = new ArgvInput(['phinx', 'migrate']);
        $output = new BufferedOutput();

        $app = new PhinxApplication();
        $app->setAutoExit(false);
        $rc = $app->run($input, $output);
        $res = $output->fetch();
        if ($rc !== 0) {
            throw new SetupException(self::ERR_DB_PHINX_FAILURE, $res);
        }

        return $res;
    }

    /**
     * @param array $db_config
     * @throws RuntimeException
     * @return string
     */
    public function run(array $db_config): string
    {
        $db_exists = $this->checkDatabaseExists($db_config['db_name']);
        if (!$db_exists) {
            if ($db_config['create_db']) {
                $this->createDatabase($db_config['db_name']);
            } else {
                throw new RuntimeException(self::ERR_DB_NOT_FOUND);
            }
        }

        // create the new user, if needed
        if ($db_config['alternate_user']) {
            if ($db_config['create_user']) {
                $this->createUser($db_config['db_name'], $db_config['username'], $db_config['password']);
            }

            if (!$this->userExists($db_config['username'])) {
                throw new RuntimeException(self::ERR_DB_USER_NOT_FOUND);
            }
        }

        $this->checkDatabaseAccess($db_config['db_name']);

        // if requested. drop tables first
        if ($db_config['drop_tables']) {
            $this->dropTables();
        }

        // write db config now that database and access is configured
        $this->writeDatabaseConfig($db_config);
        $this->setupFulltext();

        return $this->migrateDatabase();
    }

    private function setupFulltext(): void
    {
        // disable the full-text search feature for certain mysql server users
        $mysql_version = DB_Helper::getInstance(false)->getOne('SELECT VERSION()');
        preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $mysql_version, $matches);
        $enable_fulltext = version_compare($matches[1], '4.0.23', '>');
        Setup::save(['enable_fulltext' => $enable_fulltext]);
    }

    /**
     * Update database config with db name.
     * Initial database config was written by Setup.
     *
     * @param array $db_config
     */
    private function writeDatabaseConfig(array $db_config): void
    {
        $setup = [];
        $setup['database'] = $db_config['db_name'];

        if ($db_config['alternate_user']) {
            $setup['username'] = $db_config['username'];
            $setup['password'] = $db_config['password'];
        }

        Setup::save(['database' => $setup]);
    }

    private function getDb(): AdapterInterface
    {
        try {
            return DB_Helper::getInstance(false);
        } catch (DatabaseException $e) {
        }

        $err = $e->getMessage();

        // Given such PDO Exception:
        // "SQLSTATE[HY000] [2002] No such file or directory"
        // indicate that mysql default socket may be wrong
        if (strpos($err, 'No such file or directory') !== false) {
            $ini = 'pdo_mysql.default_socket';
            $err .= sprintf(". Please check that PHP ini parameter $ini='%s' is correct", ini_get($ini));
        }

        throw new RuntimeException($err, $e->getCode());
    }

    private function checkDatabaseExists(string $database): bool
    {
        return $this->conn->getOne('SHOW DATABASES LIKE ?', [$database]) !== null;
    }

    private function createDatabase(string $db_name): void
    {
        try {
            $this->conn->query("CREATE DATABASE `{$db_name}`");
        } catch (DatabaseException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    private function dropTables(): void
    {
        foreach (Table::getTableList() as $table) {
            $stmt = "DROP TABLE IF EXISTS `$table`";
            try {
                $this->conn->query($stmt);
            } catch (DatabaseException $e) {
                throw new RuntimeException($e->getMessage());
            }
        }
    }

    /**
     * @return array
     */
    private function getUserList(): array
    {
        // avoid "1046 ** No database selected" error
        $this->conn->query('USE mysql');
        try {
            $users = $this->conn->getColumn('SELECT DISTINCT User FROM user');
        } catch (DatabaseException $e) {
            // if the user cannot select from the mysql.user table, then return an empty list
            return [];
        }

        return $users;
    }

    private function userExists(string $user): bool
    {
        $user_list = $this->getUserList();

        return in_array($user, $user_list, true);
    }

    private function createUser(string $db_name, string $user, string $password): void
    {
        if ($this->userExists($user)) {
            return;
        }

        $permissions = 'SELECT, UPDATE, DELETE, INSERT, ALTER, DROP, CREATE, INDEX';
        $stmt
            = "GRANT {$permissions} ON `{$db_name}`.* TO ?@'%' IDENTIFIED BY ?";
        try {
            $this->conn->query($stmt, [$user, $password]);
        } catch (DatabaseException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @return array
     */
    private function getTableList(): array
    {
        return $this->conn->getColumn('SHOW TABLES');
    }
}
