<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Sterling Hughes <sterling@php.net>                          |
// |          Antônio Carlos Venâncio Júnior <floripa@php.net>            |
// +----------------------------------------------------------------------+
//
// $Id: s.sybase.php 1.1 02/08/22 04:15:51-00:00 jpm $
//
// Database independent query interface definition for PHP's Sybase
// extension.
//

require_once 'DB/common.php';

class DB_sybase extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();

    // }}}
    // {{{ constructor

    /**
     * DB_sybase constructor.
     *
     * @access public
     */
    function DB_sybase()
    {
        $this->DB_common();
        $this->phptype = 'sybase';
        $this->dbsyntax = 'sybase';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => false,
            'limit' => 'emulate'
        );
        $this->errorcode_map = array(
            102   => DB_ERROR_SYNTAX,
            105   => DB_ERROR_SYNTAX,
            156   => DB_ERROR_SYNTAX,
            208   => DB_ERROR_NOSUCHTABLE,
            257   => DB_ERROR_INVALID_NUMBER,
            3701  => DB_ERROR_NOSUCHTABLE,
            10304 => DB_ERROR_ACCESS_VIOLATION,
            10330 => DB_ERROR_ACCESS_VIOLATION,
            10331 => DB_ERROR_ACCESS_VIOLATION
        );
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('sybase') && !DB::assertExtension('sybase_ct'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        $user = $dsninfo['username'];
        $pw   = $dsninfo['password'];

        $interface = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
        $connect_function = $persistent ? 'sybase_pconnect' : 'sybase_connect';

        if ($interface && $user && $pw) {
            $conn = $connect_function($interface, $user, $pw);
        } else {
            $conn = FALSE;
        }

        if (!$conn) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = @sybase_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the last server error messge (if any)
     *
     * @return string sybase last error message
     */
    function errorNative()
    {
        return sybase_get_last_message();
    }

    // }}}
    // {{{ errorCode()

    function errorCode($errormsg = '')
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/Incorrect syntax near [\"\'].+[\"\']\./'
                    => 102,
                '/^Unclosed quote before the character string [\"\'].*[\"\']\./'
                    => 105,
                '/Incorrect syntax near the keyword [\"\'].+[\"\']\./'
                    => 156,
                '/Implicit conversion from datatype [\"\'].+[\"\'] to [\"\'].+[\"\'] is not allowed\./'
                    => 257,
                '/Cannot drop the table [\"\'].+[\"\'], because it doesn\'t exist in the system catalogs\./'
                    => 3701,
                '/Only the owner of object [\"\'].+[\"\'] or a user with System Administrator \(SA\) role can run this command\./'
                    => 10304,
                '/^.+ permission denied on object .+, database .+, owner .+/'
                    => 10330,
                '/^.* permission denied, database .+, owner .+/'
                    => 10331,
                '/[^.*] not found\./'
                    => 208
            );
        }
        while (list($regexp, $code) = each($error_regexps)) {
            if (preg_match($regexp, $errormsg)) {
                $error['code'] = $code;
            }
        }
        $error['userinfo'] = $errormsg;
        if (isset($error['code']) && isset($this->errorcode_map[$error['code']])) {
            $error['message'] = $this->errorcode_map[$error['code']];
        } else {
            return DB_ERROR;
        }
        return $error;
    }

    // }}}
    // {{{ sybaseRaiseError()

    function sybaseRaiseError($errno = null)
    {
        $native = $this->errorNative();
        $error = $this->errorCode($native);
        return $this->raiseError($error['message'], null, null, $error['userinfo'], $error['code']);
    }
    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to Sybase and return the results as a Sybase resource
     * identifier.
     *
     * @param the SQL query
     *
     * @access public
     *
     * @return mixed returns a valid Sybase result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error is
     * returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @sybase_query($query, $this->connection);
        if (!$result) {
            return $this->sybaseRaiseError();
        }
        if (is_resource($result)) {
            $numrows = $this->numRows($result);
            if (is_object($numrows)) {
                return $numrows;
            }
            $this->num_rows[$result] = $numrows;
            return $result;
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return DB::isManip($query) ? DB_OK : $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal sybase result pointer to the next available result
     *
     * @param a valid sybase result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data (it uses fetchInto for that)
     * @param $result Sybase result identifier
     * @param   $fetchmode  format of fetched row array
     * @param   $rownum     the absolute row number to fetch
     *
     * @return  array   a row of data, or false on error
     */
    function &fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum=null)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $res = $this->fetchInto ($result, $arr, $fetchmode, $rownum);
        if ($res !== DB_OK) {
            return $res;
        }
        return $arr;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result Sybase result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param   $rownum the row number to fetch
     * @access public
     *
     * @return int DB_OK on success, a null on failure
     */
    function fetchInto($result, &$ar, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!sybase_data_seek($result, $rownum)) {
                return $this->sybaseRaiseError();
            }
        }
        $ar = ($fetchmode & DB_FETCHMODE_ASSOC) ? @sybase_fetch_array($result) : @sybase_fetch_row($result);
        if (!$ar) {
            // reported not work as seems that sybase_get_last_message()
            // always return a message here
            //if ($errmsg = sybase_get_last_message()) {
            //    return $this->sybaseRaiseError($errmsg);
            //} else {
                return null;
            //}
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result Sybase result identifier or DB statement identifier
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return @sybase_free_result($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        return true;
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result Sybase result identifier
     *
     * @access public
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @sybase_num_fields($result);
        if (!$cols) {
            return $this->sybaseRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result Sybase result identifier
     *
     * @access public
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @sybase_num_rows($result);
        if ($rows === null) {
            return $this->sybaseRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the data manipulation
     * query.  For other queries, this function returns 0.
     *
     * @return number of rows affected by the last query
     */
    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $result = @sybase_affected_rows($this->connection);
        } else {
            $result = 0;
        }
        return $result;
     }

    // }}}
    // {{{ getSpecialQuery()

    /**
    * Returns the query needed to get some backend info
    * @param string $type What kind of info you want to retrieve
    * @return string The SQL query string
    */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                $sql = "select name from sysobjects where type = 'U' order by name";
                break;
            case 'views':
                $sql = "select name from sysobjects where type = 'V'";
                break;
            default:
                return null;
        }
        return $sql;
    }

    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>