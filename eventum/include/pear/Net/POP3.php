<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002, Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.| 
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+
//
// $Id$

require_once('Net/Socket.php');

/**
*  +----------------------------- IMPORTANT ------------------------------+
*  | Usage of this class compared to native php extensions such as IMAP   |
*  | is slow and may be feature deficient. If available you are STRONGLY  |
*  | recommended to use the php extensions.                               |
*  +----------------------------------------------------------------------+
*
* POP3 Access Class
*
* For usage see the example script
*/

define('NET_POP3_STATE_DISCONNECTED',  1, true);
define('NET_POP3_STATE_AUTHORISATION', 2, true);
define('NET_POP3_STATE_TRANSACTION',   4, true);

class Net_POP3 {

    /*
    * Some basic information about the mail drop
    * garnered from the STAT command
    *
    * @var array
    */
    var $_maildrop;

    /*
    * Used for APOP to store the timestamp
    *
    * @var string
    */
    var $_timestamp;

    /*
    * Timeout that is passed to the socket object
    *
    * @var integer
    */
    var $_timeout;

    /*
    * Socket object
    *
    * @var object
    */
    var $_socket;

    /*
    * Current state of the connection. Used with the
    * constants defined above.
    *
    * @var integer
    */
    var $_state;

    /*
    * Hostname to connect to
    *
    * @var string
    */
    var $_host;

    /*
    * Port to connect to
    *
    * @var integer
    */
    var $_port;

    /*
    * Constructor. Sets up the object variables, and instantiates
    * the socket object.
    *
    */
    function Net_POP3()
    {
        $this->_timestamp =  ''; // Used for APOP
        $this->_maildrop  =  array();
        $this->_timeout   =  3;
        $this->_state     =  NET_POP3_STATE_DISCONNECTED;
        $this->_socket    =& new Net_Socket();
    }

    /*
    * Connects to the given host on the given port.
    * Also looks for the timestamp in the greeting
    * needed for APOP authentication
    *
    * @param  $host Hostname/IP address to connect to
    * @param  $port Port to use to connect to on host
    * @return bool  Success/Failure
    */
    function connect($host = 'localhost', $port = 110)
    {
        $this->_host = $host;
        $this->_port = $port;

        $result = $this->_socket->connect($host, $port, false, $this->_timeout);
        if ($result === true) {
            $data = $this->_socket->readLine();
            if (@substr($data, 0, 3) == '+OK') {
                // Check for string matching apop timestamp
                if (preg_match('/<.+@.+>/U', $data, $matches)) {
                    $this->_timestamp = $matches[0];
                }
                $this->_maildrop = array();
                $this->_state    = NET_POP3_STATE_AUTHORISATION;
                return true;
            }
        }

        $this->_socket->disconnect();
        return false;
    }

    /*
    * Disconnect function. Sends the QUIT command
    * and closes the socket.
    *
    * @return bool Success/Failure
    */
    function disconnect()
    {
        return $this->_cmdQuit();
    }

    /*
    * Performs the login procedure. If there is a timestamp
    * stored, APOP will be tried first, then basic USER/PASS.
    *
    * @param  $user Username to use
    * @param  $pass Password to use
    * @param  $apop Whether to try APOP first
    * @return bool  Success/Failure
    */
    function login($user, $pass, $apop = true)
    {
        if ($this->_state == NET_POP3_STATE_AUTHORISATION) {
            // Try APOP authentication first
            if ($apop AND $this->_cmdApop($user, $pass)) {
                $this->_state = NET_POP3_STATE_TRANSACTION;
                return true;
            }

            // APOP failed or not desired, use basic authentication
            if ($this->_cmdUser($user) AND $this->_cmdPass($pass)) {
                $this->_state = NET_POP3_STATE_TRANSACTION;
                return true;
            }
        }

        return false;
    }

    /*
    * Returns the raw headers of the specified message.
    *
    * @param  $msg_id Message number
    * @return mixed   Either raw headers or false on error
    */
    function getRawHeaders($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            return $this->_cmdTop($msg_id, 0);
        }
        
        return false;
    }

    /*
    * Returns the  headers of the specified message in an
    * associative array. Array keys are the header names, array
    * values are the header values. In the case of multiple headers
    * having the same names, eg Received:, the array value will be 
    * an indexed array of all the header values.
    *
    * @param  $msg_id Message number
    * @return mixed   Either array of headers or false on error
    */
    function getParsedHeaders($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $raw_headers = rtrim($this->getRawHeaders($msg_id));
            $raw_headers = preg_replace("/\r\n[ \t]+/", ' ', $raw_headers); // Unfold headers
            $raw_headers = explode("\r\n", $raw_headers);
            foreach ($raw_headers as $value) {
                $name  = substr($value, 0, $pos = strpos($value, ':'));
                $value = ltrim(substr($value, $pos + 1));
                if (isset($headers[$name]) AND is_array($headers[$name])) {
                    $headers[$name][] = $value;
                } elseif (isset($headers[$name])) {
                    $headers[$name] = array($headers[$name], $value);
                } else {
                    $headers[$name] = $value;
                }
            }

            return $headers;
        }
        
        return false;
    }

    /*
    * Returns the body of the message with given message number.
    *
    * @param  $msg_id Message number
    * @return mixed   Either message body or false on error
    */
    function getBody($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $msg = $this->_cmdRetr($msg_id);
            return substr($msg, strpos($msg, "\r\n\r\n")+4);
        }

        return false;
    }

    /*
    * Returns the entire message with given message number.
    *
    * @param  $msg_id Message number
    * @return mixed   Either entire message or false on error
    */
    function getMsg($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            return $this->_cmdRetr($msg_id);
        }
        
        return false;
    }

    /*
    * Returns the size of the maildrop
    *
    * @return mixed Either size of maildrop or false on error
    */
    function getSize()
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            if (isset($this->_maildrop['size'])) {
                return $this->_maildrop['size'];
            } else {
                list(, $size) = $this->_cmdStat();
                return $size;
            }
        }
        
        return false;
    }

    /*
    * Returns number of messages in this maildrop
    *
    * @return mixed Either number of messages or false on error
    */
    function numMsg()
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            if (isset($this->_maildrop['num_msg'])) {
                return $this->_maildrop['num_msg'];
            } else {
                list($num_msg, ) = $this->_cmdStat();
                return $num_msg;
            }
        }
        
        return false;
    }

    /*
    * Marks a message for deletion. Only will be deleted if the
    * disconnect() method is called.
    *
    * @param  $msg_id Message to delete
    * @return bool Success/Failure
    */
    function deleteMsg($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            return $this->_cmdDele($msg_id);
        }
        
        return false;
    }

    /*
    * Combination of LIST/UIDL commands, returns an array
    * of data
    *
    * @param  $msg_id Optional message number
    * @return mixed Array of data or false on error
    */
    function getListing($msg_id = null)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            if (!isset($msg_id)){
                if ($list = $this->_cmdList()) {
                    if ($uidl = $this->_cmdUidl()) {
                        foreach ($uidl as $i => $value) {
                            $list[$i]['uidl'] = $value['uidl'];
                        }
                    }
                    
                    return $list;
                }
            } else {
                if ($list = $this->_cmdList($msg_id) AND $uidl = $this->_cmdUidl($msg_id)) {
                    return array_merge($list, $uidl);
                }
            }
        }
        
        return false;
    }

    /*
    * Sends the USER command
    *
    * @param  $user Username to send
    * @return bool  Success/Failure
    */
    function _cmdUser($user)
    {
        if ($this->_state == NET_POP3_STATE_AUTHORISATION) {
            return (bool)$this->_sendCmd('USER ' . $user);
        }

        return false;
    }


    /*
    * Sends the PASS command
    *
    * @param  $pass Password to send
    * @return bool  Success/Failure
    */
    function _cmdPass($pass)
    {
        if ($this->_state == NET_POP3_STATE_AUTHORISATION) {
            return (bool)$this->_sendCmd('PASS ' . $pass);
        }

        return false;
    }


    /*
    * Sends the STAT command
    *
    * @return mixed Indexed array of number of messages and 
    *               maildrop size, or false on error.
    */
    function _cmdStat()
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $data = $this->_sendCmd('STAT');
            if ($data) {
                sscanf($data, '+OK %d %d', $msg_num, $size);
                $this->_maildrop['num_msg'] = $msg_num;
                $this->_maildrop['size']    = $size;
    
                return array($msg_num, $size);
            }
        }

        return false;
    }


    /*
    * Sends the LIST command
    *
    * @param  $msg_id Optional message number
    * @return mixed   Indexed array of msg_id/msg size or
    *                 false on error
    */
    function _cmdList($msg_id = null)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            if (!isset($msg_id)) {
                $data = $this->_sendCmd('LIST');
                if ($data) {
                    $data = $this->_getMultiline();
                    $data = explode("\r\n", $data);
                    foreach ($data as $line) {
                        sscanf($line, '%s %s', $msg_id, $size);
                        $return[] = array('msg_id' => $msg_id, 'size' => $size);
                    }
                    return $return;
                }
            } else {
                $data = $this->_sendCmd('LIST ' . $msg_id);
                if ($data) {
                    sscanf($data, '+OK %d %d', $msg_id, $size);
                    return array('msg_id' => $msg_id, 'size' => $size);
                }
            }
        }
        
        return false;
    }


    /*
    * Sends the RETR command
    *
    * @param  $msg_id The message number to retrieve
    * @return mixed   The message or false on error
    */
    function _cmdRetr($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $data = $this->_sendCmd('RETR ' . $msg_id);
            if ($data) {
                $data = $this->_getMultiline();
                return $data;
            }
        }

        return false;
    }


    /*
    * Sends the DELE command
    *
    * @param  $msg_id Message number to mark as deleted
    * @return bool Success/Failure
    */
    function _cmdDele($msg_id)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            return (bool)$this->_sendCmd('DELE ' . $msg_id);
        }

        return false;
    }


    /*
    * Sends the NOOP command
    *
    * @return bool Success/Failure
    */
    function _cmdNoop()
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $data = $this->_sendCmd('NOOP');
            if ($data) {
                return true;
            }
        }

        return false;
    }

    /*
    * Sends the RSET command
    *
    * @return bool Success/Failure
    */
    function _cmdRset()
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {
            $data = $this->_sendCmd('RSET');
            if ($data) {
                return true;
            }
        }

        return false;
    }

    /*
    * Sends the QUIT command
    *
    * @return bool Success/Failure
    */
    function _cmdQuit()
    {
        $data = $this->_sendCmd('QUIT');
        $this->_state = NET_POP3_STATE_DISCONNECTED;
        $this->_socket->disconnect();

        return (bool)$data;
    }


    /*
    * Sends the TOP command
    *
    * @param  $msg_id    Message number
    * @param  $num_lines Number of lines to retrieve
    * @return mixed Message data or false on error
    */
    function _cmdTop($msg_id, $num_lines)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {

            $data = $this->_sendCmd('TOP ' . $msg_id . ' ' . $num_lines);
            if ($data) {
                return $this->_getMultiline();
            }
        }
        
        return false;
    }

    /*
    * Sends the UIDL command
    *
    * @param  $msg_id Message number
    * @return mixed indexed array of msg_id/uidl or false on error
    */
    function _cmdUidl($msg_id = null)
    {
        if ($this->_state == NET_POP3_STATE_TRANSACTION) {

            if (!isset($msg_id)) {
                $data = $this->_sendCmd('UIDL');
                if ($data) {
                    $data = $this->_getMultiline();
                    $data = explode("\r\n", $data);
                    foreach ($data as $line) {
                        sscanf($line, '%d %s', $msg_id, $uidl);
                        $return[] = array('msg_id' => $msg_id, 'uidl' => $uidl);
                    }

                    return $return;
                }
            } else {

                $data = $this->_sendCmd('UIDL ' . $msg_id);
                sscanf($data, '+OK %d %s', $msg_id, $uidl);
                return array('msg_id' => $msg_id, 'uidl' => $uidl);
            }
        }
        
        return false;
    }

    /*
    * Sends the APOP command
    *
    * @param  $user Username to send
    * @param  $pass Password to send
    * @return bool Success/Failure
    */
    function _cmdApop($user, $pass)
    {
        if ($this->_state == NET_POP3_STATE_AUTHORISATION) {

            if (!empty($this->_timestamp)) {
                $data = $this->_sendCmd('APOP ' . $user . ' ' . md5($this->_timestamp . $pass));
                if ($data) {
                    $this->_state = NET_POP3_STATE_TRANSACTION;
                    return true;
                }
            }
        }
        
        return false;
    }

    /*
    * Sends a command, checks the reponse, and 
    * if good returns the reponse, other wise
    * returns false.
    *
    * @param  $cmd  Command to send (\r\n will be appended)
    * @return mixed First line of response if successful, otherwise false
    */
    function _sendCmd($cmd)
    {
        $result = $this->_socket->writeLine($cmd);
        if (!PEAR::isError($result) AND $result) {
            $data = $this->_socket->readLine();
            if (!PEAR::isError($data) AND substr($data, 0, 3) == '+OK') {
                return $data;
            }
        }

        return false;
    }

    /*
    * Reads a multiline reponse and returns the data
    *
    * @return string The reponse.
    */
    function _getMultiline()
    {
        $data = '';
        while(($tmp = $this->_socket->readLine()) != '.') {
            if (substr($tmp, 0, 2) == '..') {
                $tmp = substr($tmp, 1);
            }
            $data .= $tmp . "\r\n";
        }

        return substr($data, 0, -2);
    }
}

?>
