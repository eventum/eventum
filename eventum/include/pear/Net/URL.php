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
// Net_URL Class

class Net_URL {

    /**
    * Full url
    * @var string
    */
    var $url;
    
    /**
    * Protocol
    * @var string
    */
    var $protocol;

    /**
    * Username
    * @var string
    */
    var $username;

    /**
    * Password
    * @var string
    */
    var $password;

    /**
    * Host
    * @var string
    */
    var $host;
    
    /**
    * Port
    * @var integer
    */
    var $port;
    
    /**
    * Path
    * @var string
    */
    var $path;
    
    /**
    * Query string
    * @var array
    */
    var $querystring;

    /**
    * Anchor
    * @var string
    */
    var $anchor;

    /**
    * Constructor
    *
    * Parses the given url and stores the various parts
    * Defaults are used in certain cases
    *
    * @param $url The url
    */
    function Net_URL($url = null)
    {
        global $HTTP_SERVER_VARS;

        /**
        * Figure out host/port
        */
        if (!empty($HTTP_SERVER_VARS['HTTP_HOST']) AND preg_match('/^(.*)(:([0-9]+))?$/U', $HTTP_SERVER_VARS['HTTP_HOST'], $matches)) {
            $host = $matches[1];
            if (!empty($matches[3])) {
                $port = $matches[3];
            } else {
                $port = '80';
            }
        }

        $this->url         = $url;
        $this->protocol    = 'http' . (@$HTTP_SERVER_VARS['HTTPS'] == 'on' ? 's' : '');
        $this->user        = '';
        $this->pass        = '';
        $this->host        = !empty($host) ? $host : (isset($HTTP_SERVER_VARS['SERVER_NAME']) ? $HTTP_SERVER_VARS['SERVER_NAME'] : 'localhost');
        $this->port        = !empty($port) ? $port : (isset($HTTP_SERVER_VARS['SERVER_PORT']) ? $HTTP_SERVER_VARS['SERVER_PORT'] : 80);
        $this->path        = $HTTP_SERVER_VARS['PHP_SELF'];
        $this->querystring = isset($HTTP_SERVER_VARS['QUERY_STRING']) ? $this->_parseRawQuerystring($HTTP_SERVER_VARS['QUERY_STRING']) : null;
        $this->anchor      = '';

        // Parse the uri and store the various parts
        if (!empty($url)) {
            $urlinfo = parse_url($url);
    
            // Protocol
            if (!empty($urlinfo['scheme'])) {
                $this->protocol = $urlinfo['scheme'];
            }
    
            // Username
            if (!empty($urlinfo['user'])) {
                $this->user = $urlinfo['user'];
            }
    
            // Password
            if (!empty($urlinfo['pass'])) {
                $this->pass = $urlinfo['pass'];
            }
    
            // Host
            if (!empty($urlinfo['host'])) {
                $this->host = $urlinfo['host'];
            }
    
            // Port
            if (!empty($urlinfo['port'])) {
                $this->port = $urlinfo['port'];
            }
    
            // Path
            if (!empty($urlinfo['path'])) {
                if ($urlinfo['path'][0] == '/') {
                    $this->path = $urlinfo['path'];
                } else {
                    $path = dirname($this->path) == DIRECTORY_SEPARATOR ? '' : dirname($this->path);
                    $this->path = sprintf('%s/%s', $path, $urlinfo['path']);
                }
            } else {
                $this->path = '/';
            }
    
            // Querystring
            $this->querystring = !empty($urlinfo['query']) ? $this->_parseRawQueryString($urlinfo['query']) : array();
    
            // Anchor
            if (!empty($urlinfo['fragment'])) {
                $this->anchor = $urlinfo['fragment'];
            }
        }
    }

    /**
    * Returns full url
    *
    * @return string Full url
    * @access public
    */
    function getURL()
    {
        $querystring = $this->getQueryString();

        $this->url = $this->protocol . '://'
                   . $this->user . (!empty($this->pass) ? ':' : '')
                   . $this->pass . (!empty($this->user) ? '@' : '')
                   . $this->host . ($this->port == '80' ? '' : ':' . $this->port)
                   . $this->path
                   . (!empty($querystring) ? '?' . $querystring : '')
                   . (!empty($this->anchor) ? '#' . $this->anchor : '');

        return $this->url;
    }

    /**
    * Adds a querystring item
    *
    * @param $name Name of item
    * @param $value Value of item
    * @param $preencoded Whether value is urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        $this->querystring[$name] = $preencoded ? $value : urlencode($value);
        if ($preencoded) {
            $this->querystring[$name] = $value;
        } else {
            $this->querystring[$name] = is_array($value)? array_map('urlencode', $value): urlencode($value);
        }
    }    

    /**
    * Removes a querystring item
    *
    * @param $name Name of item
    * @access public<>
    */
    function removeQueryString($name)
    {
        if (isset($this->querystring[$name])) {
            unset($this->querystring[$name]);
        }
    }    
    
    /**
    * Sets the querystring to literally what you supply
    *
    * @param $querystring The querystring data. Should be of the format foo=bar&x=y etc
    * @access public
    */
    function addRawQueryString($querystring)
    {
        $this->querystring = $this->_parseRawQueryString($querystring);
    }
    
    /**
    * Returns flat querystring
    *
    * @return string Querystring
    * @access public
    */
    function getQueryString()
    {
        if (!empty($this->querystring)) {
            foreach ($this->querystring as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $querystring[] = sprintf('%s[%s]=%s', $name, $k, $v);
                    }
                } elseif ($value) {
                    $querystring[] = $name . '=' . $value;
                } else {
                    $querystring[] = $name;
                }
            }
            $querystring = implode('&', $querystring);
        } else {
            $querystring = '';
        }

        return $querystring;
    }

    /**
    * Parses raw querystring and returns an array of it
    *
    * @param  string  $querystring The querystring to parse
    * @return array                An array of the querystring data
    * @access private
    */
    function _parseRawQuerystring($querystring)
    {
        parse_str($querystring, $qs);

        foreach ($qs as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = rawurlencode($v);
                }
                $qs[$key] = $value;
            } elseif ($value) {
                $qs[$key] = rawurlencode($value);
            }
        }        

        return $qs;
    }
}
?>