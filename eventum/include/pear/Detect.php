<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP version 4.2                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// $Id: s.Detect.php 1.1 02/08/22 04:15:51-00:00 jpm $

// }}}
// {{{ description

// Net_UserAgent_Detect Determine the Web browser, version, and platform from an HTTP user agent string

// }}}
// {{{ constants

define('NET_USERAGENT_DETECT_BROWSER',  'browser');
define('NET_USERAGENT_DETECT_OS',       'os');
define('NET_USERAGENT_DETECT_FEATURES', 'features');
define('NET_USERAGENT_DETECT_QUIRKS',   'quirks');
define('NET_USERAGENT_DETECT_ACCEPT',   'accept');
define('NET_USERAGENT_DETECT_ALL',      'all');

// }}}

// {{{ class Net_UserAgent_Detect

/**
 * The Net_UserAgent_Detect object does a number of tests on an HTTP user
 * agent string.  The results of these tests are available via methods of
 * the object.
 *
 * This module is based upon the JavaScript browser detection code
 * available at http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html.
 * This module had many influences from the lib/Browser.php code in
 * version 1.3 of Horde.
 *
 * @version  Revision: 0.1
 * @author   Dan Allen <dan@mojavelinux.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @access   public
 * @since    PHP 4.1
 * @package  Net_UserAgent
 */

// }}}
class Net_UserAgent_Detect {
    // {{{ properties

    /**
     * User agent string that is being analyzed
     * @var string $userAgent
     */
    var $userAgent = '';

    /**
     * Array that stores all of the flags for the vendor and version of
     * the different browsers.  The flags key values are show in the array.
     * @var array $browser
     */
    var $browser = array('ns', 'ns2', 'ns3', 'ns4', 'ns4up', 'nav', 'ns6', 'ns6up', 'gecko', 'ie', 'ie3', 'ie4', 'ie4up', 'ie5', 'ie5_5', 'ie5up', 'ie6', 'ie6up', 'opera', 'opera2', 'opera3', 'opera4', 'opera5', 'opera5up', 'aol', 'aol3', 'aol4', 'aol5', 'aol6', 'aol7', 'webtv', 'aoltv', 'tvnavigator', 'hotjava', 'hotjava3', 'hotjava3up');

    /**
     * Array that stores all of the flags for the operating systems, and in some cases
     * the versions of those operating systems (windows)
     * @var array $os
     */
    var $os = array('win', 'win95', 'win16', 'win31', 'win9x', 'win98', 'winme', 'win2k', 'winnt', 'os2', 'mac', 'mac68k', 'macppc', 'linux', 'unix', 'vms', 'sun', 'sun4', 'sun5', 'suni86', 'irix', 'irix5', 'irix6', 'hpux', 'hpux9', 'hpux10', 'aix', 'aix1', 'aix2', 'aix3', 'aix4', 'sco', 'unixware', 'mpras', 'reliantunix', 'dec', 'sinix', 'freebsd', 'bsd');

    /**
     * Array that stores credentials for each of the browser/os combinations.  These allow
     * quick access to determine if the current client has a feature that is going to be
     * implemented in the script.
     * @var array $features
     */
    var $features = array(
        'javascript'   => false,
        'dhtml'        => false,
        'dom'          => false,
        'sidebar'      => false,
        'gecko'        => false,
    );

    /**
     * Array which stores known issues with the given client that can be used for on the
     * fly tweaking so that the client may recieve the proper handling of this quirk.
     * @var array $quirks
     */
    var $quirks = array(
        'must_cache_forms'         => false,
        'popups_disabled'          => false,
        'empty_file_input_value'   => false,
        'cache_ssl_downloads'      => false,
        'scrollbar_in_way'         => false,
        'break_disposition_header' => false,
    );

    /**
     * The leading identifier is the very first term in the user agent string, which is
     * used to identify clients which are not Mosaic-based browsers.
     * @var string $leadingIdentifier
     */
    var $leadingIdentifier = '';

    /**
     * The full version of the client as supplied by the very first numbers in the user agent
     * @var float $version
     */
    var $version = 0;

    /**
     * The major part of the client version, which is the integer value of the version.
     * @var integer $majorVersion
     */
    var $majorVersion = 0;

    /**
     * The minor part of the client version, which is the decimal parts of the version
     * @var float $subVersion
     */
    var $subVersion = 0;

    /**
     * This level determins how much checking we do.  When time is of the essences, filtering
     * the AOL versions might seem like overkill.  There are three levels, normal|detailed|basic
     * @var string $level
     */
    var $detectOptions = array(NET_USERAGENT_DETECT_BROWSER, NET_USERAGENT_DETECT_OS, NET_USERAGENT_DETECT_FEATURES, NET_USERAGENT_DETECT_QUIRKS, NET_USERAGENT_DETECT_ACCEPT, NET_USERAGENT_DETECT_ALL);
    
    var $mimetype = array();

    var $language = array();

    var $charset = array();

    var $encoding = array();

    // }}}
    // {{{ singleton

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * Most of the time we want to evaluate the client's user agent string,
     * so we want to make sure that we only have to run the detect() function
     * once.  Therefore, all of the all of the properties and methods must map
     * to a single state.  Therefore, this function is used in place of the
     * contructor to return any open instances of the client object, and if none
     * are open will create a new instance and cache it using a static
     * variable.
     *
     * @access public 
     * @return object Net_UserAgent_Detect instance
     */
    function &singleton($in_userAgent = null, $in_detect = null) 
    { 
        static $instance;
       
        if (!isset($instance)) { 
            $instance = new Net_UserAgent_Detect($in_userAgent); 
        }
        
        return $instance; 
    }

    // }}}
    // {{{ constructor

    function Net_UserAgent_Detect($in_userAgent = null, $in_detect = null)
    {
        $this->detect($in_userAgent, $in_detect);
    }

    // }}}
    // {{{ detect()

    /**
     * Detect the user agent and prepare flags, features and quirks based on what is found
     *
     * This is the core of the Net_UserAgent_Detect class.  It moves its way through the user agent
     * string setting up the flags based on the vendors and versions of the browsers, determining
     * the OS and setting up the features and quirks owned by each of the relevant clients.
     *
     * @param  string (optional) user agent override
     *
     * @access public
     * @return void
     */
    function detect($in_userAgent = null, $in_detect = null)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        // detemine what user agent we are using
        if (is_null($in_userAgent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
            elseif (isset($GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'])) {
                $this->userAgent = $GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'];
            }
            // fallback on a default one for testing from commandline
            else {
                $this->userAgent = '';
            }
        }
        else {
            $this->userAgent = $in_userAgent;
        }

        // get the lowercase version for case-insensitive searching
        $agt = strtolower($this->userAgent);

        // figure out what we need to look for
        $detect = is_null($in_detect) ? NET_USERAGENT_DETECT_ALL : $in_detect;
        settype($detect, 'array');
        foreach($this->detectOptions as $option) {
            if (in_array($option, $detect)) {
                $detectFlags[$option] = true; 
            }
            else {
                $detectFlags[$option] = false;
            }
        }

        // initialize the flag arrays
        $brwsr =& $this->browser;
        $brwsr = array_flip($brwsr);
        $os =& $this->os;
        $os = array_flip($os);

        // Get the type and version of the client
        preg_match(";^([[:alpha:]]+)[ /\(]*[[:alpha:]]*([\d]*)\.([\d\.]*);", $agt, $matches);
        list($null, $this->leadingIdentifier, $this->majorVersion, $this->subVersion) = $matches;
        if (empty($this->leadingIdentifier)) {
            $this->leadingIdentifier = 'Unknown';
        }

        $this->version = $this->majorVersion . '.' . $this->subVersion;
    
        // Browser type
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_BROWSER]) {   
            $brwsr['konq']    = (strpos($agt, 'konqueror') !== false);
            $brwsr['text']    = (strpos($agt, 'links') !== false) || (strpos($agt, 'lynx') !== false) || (strpos($agt, 'w3m') !== false);
            $brwsr['ns']      = (strpos($agt, 'mozilla') !== false) && !(strpos($agt, 'spoofer') !== false) && !(strpos($agt, 'compatible') !== false) && !(strpos($agt, 'hotjava') !== false) && !(strpos($agt, 'opera') !== false) && !(strpos($agt, 'webtv') !== false) ? 1 : 0;
            $brwsr['ns2']     = $brwsr['ns'] && $this->majorVersion == 2;
            $brwsr['ns3']     = $brwsr['ns'] && $this->majorVersion == 3;
            $brwsr['ns4']     = $brwsr['ns'] && $this->majorVersion == 4;
            $brwsr['ns4up']   = $brwsr['ns'] && $this->majorVersion >= 4;
            // determine if this is a Netscape Navigator
            $brwsr['nav']     = $brwsr['ns'] && ((strpos($agt, ';nav') !== false) || ((strpos($agt, '; nav') !== false)));
            $brwsr['ns6']     = !$brwsr['konq'] && $brwsr['ns'] && $this->majorVersion == 5;
            $brwsr['ns6up']   = $brwsr['ns6'] && $this->majorVersion >= 5;
            $brwsr['gecko']   = (strpos($agt, 'gecko') !== false);
            $brwsr['ie']      = (strpos($agt, 'msie') !== false) && !(strpos($agt, 'opera') !== false);
            $brwsr['ie3']     = $brwsr['ie'] && $this->majorVersion < 4;
            $brwsr['ie4']     = $brwsr['ie'] && $this->majorVersion == 4 && (strpos($agt, 'msie 4') !== false);
            $brwsr['ie4up']   = $brwsr['ie'] && $this->majorVersion >= 4;
            $brwsr['ie5']     = $brwsr['ie'] && $this->majorVersion == 4 && (strpos($agt, 'msie 5.0') !== false);
            $brwsr['ie5_5']   = $brwsr['ie'] && $this->majorVersion == 4 && (strpos($agt, 'msie 5.5') !== false);
            $brwsr['ie5up']   = $brwsr['ie'] && !$brwsr['ie3'] && !$brwsr['ie4'];
            $brwsr['ie5_5up'] = $brwsr['ie'] && !$brwsr['ie3'] && !$brwsr['ie4'] && !$brwsr['ie5'];
            $brwsr['ie6']     = $brwsr['ie'] && $this->majorVersion == 4 && (strpos($agt, 'msie 6.') !== false);
            $brwsr['ie6up']   = $brwsr['ie'] && !$brwsr['ie3'] && !$brwsr['ie4'] && !$brwsr['ie5'] && !$brwsr['ie5_5'];
            $brwsr['opera']   = (strpos($agt, 'opera') !== false);
            $brwsr['opera2']  = (strpos($agt, 'opera 2') !== false) || (strpos($agt, 'opera/2') !== false);
            $brwsr['opera3']  = (strpos($agt, 'opera 3') !== false) || (strpos($agt, 'opera/3') !== false);
            $brwsr['opera4']  = (strpos($agt, 'opera 4') !== false) || (strpos($agt, 'opera/4') !== false);
            $brwsr['opera5']  = (strpos($agt, 'opera 5') !== false) || (strpos($agt, 'opera/5') !== false);
            $brwsr['opera5up'] = $brwsr['opera'] && !$brwsr['opera2'] && !$brwsr['opera3'] && !$brwsr['opera4'];

            $brwsr['aol']   = (strpos($agt, 'aol') !== false);
            $brwsr['aol3']  = $brwsr['aol'] && $brwsr['ie3'];
            $brwsr['aol4']  = $brwsr['aol'] && $brwsr['ie4'];
            $brwsr['aol5']  = (strpos($agt, 'aol 5') !== false);
            $brwsr['aol6']  = (strpos($agt, 'aol 6') !== false);
            $brwsr['aol7']  = (strpos($agt, 'aol 7') !== false);
            $brwsr['webtv'] = (strpos($agt, 'webtv') !== false); 
            $brwsr['aoltv'] = $brwsr['tvnavigator'] = (strpos($agt, 'navio') !== false) || (strpos($agt, 'navio_aoltv') !== false); 
            $brwsr['hotjava'] = (strpos($agt, 'hotjava') !== false);
            $brwsr['hotjava3'] = $brwsr['hotjava'] && $this->majorVersion == 3;
            $brwsr['hotjava3up'] = $brwsr['hotjava'] && $this->majorVersion >= 3;
        }

        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_FEATURES])) {
            // Javascript Check
            if ($brwsr['ns2'] || $brwsr['ie3']) {
                $this->setFeature('javascript', 1.0);
            }
            elseif ($brwsr['opera5up']) {
                $this->setFeature('javascript', 1.3);
            }
            elseif ($brwsr['opera'] || $brwsr['ns3']) {
                $this->setFeature('javascript', 1.1);
            }
            elseif (($brwsr['ns4'] && ($this->version <= 4.05)) || $brwsr['ie4']) {
                $this->setFeature('javascript', 1.2);
            }
            elseif ($brwsr['ie5up'] && (strpos($agt, 'mac') !== false)) {
                $this->setFeature('javascript', 1.4);
            }
            // I can't believe IE6 still has javascript 1.3, what a shitty browser
            elseif (($brwsr['ns4'] && ($this->version > 4.05)) || $brwsr['ie5up'] || $brwsr['hotjava3up']) {
                $this->setFeature('javascript', 1.3);
            }
            elseif ($brwsr['ns6up'] || $brwsr['gecko']) {
                $this->setFeature('javascript', 1.5);
            }
        }
        
        /** OS Check **/
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_OS]) {
            $os['win']   = (strpos($agt, 'win') !== false) || (strpos($agt, '16bit') !== false);
            $os['win95'] = (strpos($agt, 'win95') !== false) || (strpos($agt, 'windows 95') !== false);
            $os['win16'] = (strpos($agt, 'win16') !== false) || (strpos($agt, '16bit') !== false) || (strpos($agt, 'windows 3.1') !== false) || (strpos($agt, 'windows 16-bit') !== false);  
            $os['win31'] = (strpos($agt, 'windows 3.1') !== false) || (strpos($agt, 'win16') !== false) || (strpos($agt, 'windows 16-bit') !== false);
            $os['winme'] = (strpos($agt, 'win 9x 4.90') !== false);
            $os['win2k'] = (strpos($agt, 'windows nt 5.0') !== false);
            $os['win98'] = (strpos($agt, 'win98') !== false) || (strpos($agt, 'windows 98') !== false);
            $os['win9x'] = $os['win95'] || $os['win98'];
            $os['winnt'] = (strpos($agt, 'winnt') !== false) || (strpos($agt, 'windows nt') !== false);
            $os['win32'] = $os['win95'] || $os['winnt'] || $os['win98'] || $this->majorVersion >= 4 && (strpos($agt, 'win32') !== false) || (strpos($agt, '32bit') !== false);
            $os['os2']   = (strpos($agt, 'os/2') !== false) || (strpos($agt, 'ibm-webexplorer') !== false);
            $os['mac']   = (strpos($agt, 'mac') !== false);
            $os['mac68k']   = $os['mac'] && ((strpos($agt, '68k') !== false) || (strpos($agt, '68000') !== false));
            $os['macppc']   = $os['mac'] && ((strpos($agt, 'ppc') !== false) || (strpos($agt, 'powerpc') !== false));
            $os['sun']      = (strpos($agt, 'sunos') !== false);
            $os['sun4']     = (strpos($agt, 'sunos 4') !== false);
            $os['sun5']     = (strpos($agt, 'sunos 5') !== false);
            $os['suni86']   = $os['sun'] && (strpos($agt, 'i86') !== false);
            $os['irix']     = (strpos($agt, 'irix') !== false);
            $os['irix5']    = (strpos($agt, 'irix 5') !== false);
            $os['irix6']    = (strpos($agt, 'irix 6') !== false) || (strpos($agt, 'irix6') !== false);
            $os['hpux']     = (strpos($agt, 'hp-ux') !== false);
            $os['hpux9']    = $os['hpux'] && (strpos($agt, '09.') !== false);
            $os['hpux10']   = $os['hpux'] && (strpos($agt, '10.') !== false);
            $os['aix']      = (strpos($agt, 'aix') !== false);
            $os['aix1']     = (strpos($agt, 'aix 1') !== false);    
            $os['aix2']     = (strpos($agt, 'aix 2') !== false);    
            $os['aix3']     = (strpos($agt, 'aix 3') !== false);    
            $os['aix4']     = (strpos($agt, 'aix 4') !== false);    
            $os['linux']    = (strpos($agt, 'inux') !== false);
            $os['sco']      = (strpos($agt, 'sco') !== false) || (strpos($agt, 'unix_sv') !== false);
            $os['unixware'] = (strpos($agt, 'unix_system_v') !== false); 
            $os['mpras']    = (strpos($agt, 'ncr') !== false); 
            $os['reliant']  = (strpos($agt, 'reliantunix') !== false);
            $os['dec']      = (strpos($agt, 'dec') !== false) || (strpos($agt, 'osf1') !== false) || (strpos($agt, 'dec_alpha') !== false) || (strpos($agt, 'alphaserver') !== false) || (strpos($agt, 'ultrix') !== false) || (strpos($agt, 'alphastation') !== false); 
            $os['sinix']    = (strpos($agt, 'sinix') !== false);
            $os['freebsd']  = (strpos($agt, 'freebsd') !== false);
            $os['bsd']      = (strpos($agt, 'bsd') !== false);
            $os['unix']     = (strpos($agt, 'x11') !== false) || (strpos($agt, 'unix') !== false) || $os['sun'] || $os['irix'] || $os['hpux'] || $os['sco'] || $os['unixware'] || $os['mpras'] || $os['reliant'] || $os['dec'] || $os['sinix'] || $os['aix'] || $os['linux'] || $os['bsd'] || $os['freebsd'];
            $os['vms']      = (strpos($agt, 'vax') !== false) || (strpos($agt, 'openvms') !== false);
        }

        // Setup the quirks
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_QUIRKS])) {
            if ($brwsr['konq']) {
                $this->setQuirk('empty_file_input_value');
            }

            if ($brwsr['ie']) {
                $this->setQuirk('cache_ssl_downloads');
            }

            if ($brwsr['ie6']) {
                $this->setQuirk('scrollbar_in_way');
            }

            if ($brwsr['ie5']) {
                $this->setQuirk('break_disposition_header');
            }

            if ($brwsr['ns6']) {
                $this->setQuirk('popups_disabled');
                $this->setQuirk('must_cache_forms');
            }
        }
            
        // Set features
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_FEATURES])) {
            if ($brwsr['gecko']) {
                preg_match(';gecko/([\d]+)\b;i', $agt, $matches);
                $this->setFeature('gecko', $matches[1]);
            }

            if ($brwsr['ns6up'] || $brwsr['opera5up'] || $brwsr['konq']) {
                $this->setFeature('dom');
            }

            if ($brwsr['ie4up'] || $brwsr['ns4up'] || $brwsr['opera5up'] || $brwsr['konq']) {
                $this->setFeature('dhtml');
            }
        }

        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_ACCEPT]) {
            $mimetypes = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT'), 0, strpos(getenv('HTTP_ACCEPT') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            $this->setAcceptType((array) $mimetypes, 'mimetype');

            $languages = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_LANGUAGE'), 0, strpos(getenv('HTTP_ACCEPT_LANGUAGE') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            if (empty($languages)) {
                $languages = 'en';
            }

            $this->setAcceptType((array) $languages, 'language');

            $encodings = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_ENCODING'), 0, strpos(getenv('HTTP_ACCEPT_ENCODING') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            $this->setAcceptType((array) $encodings, 'encoding');
            
            $charsets = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_CHARSET'), 0, strpos(getenv('HTTP_ACCEPT_CHARSET') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);

            $this->setAcceptType((array) $charsets, 'charset');
        }
    }
    
    // }}}
    // {{{ isBrowser()

    /**
     * Look up the provide browser flag and return a boolean value
     *
     * Given one of the flags listed in the properties, this function will return
     * the value associated with that flag.
     *
     * @param  string $in_match flag to lookup
     *
     * @access public
     * @return boolean whether or not the browser satisfies this flag
     */
    function isBrowser($in_match)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $match = strtolower($in_match);
        return isset($this->browser[$match]) ? $this->browser[$match] : false;
    }

    // }}}
    // {{{ getBrowser()

    /**
     * Since simply returning the "browser" is somewhat ambiguous since there
     * are different ways to classify the browser, this function works by taking
     * an expect list and returning the string of the first match, so put the important
     * ones first in the array.
     *
     * @param  array $in_expectList the browser flags to search for
     *
     * @access public
     * @return string first flag that matches
     */
    function getBrowser($in_expectList)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        foreach((array) $in_expectList as $browser) {
            if (!empty($this->browser[strtolower($browser)])) {
                return $browser;
            }
        }
    }

    // }}}
    // {{{ getBrowserString()

    /**
     * This function returns the vendor string corresponding to the flag.
     *
     * Either use the default matches or pass in an associative array of
     * flags and corresponding vendor strings.  This function will find
     * the highest version flag and return the vendor string corresponding
     * to the appropriate flag.  Be sure to pass in the flags in ascending order
     * if you want a basic matches first, followed by more detailed matches.
     *
     * @param  array $in_vendorStrings (optional) array of flags matched with vendor strings
     *
     * @access public
     * @return string vendor string matches appropriate flag
     */
    function getBrowserString($in_vendorStrings = array (
        'ie'       => 'Microsoft Internet Explorer',
        'ie4up'    => 'Microsoft Internet Explorer 4.x',
        'ie5up'    => 'Microsoft Internet Explorer 5.x',
        'ie6up'    => 'Microsoft Internet Explorer 6.x',
        'opera4'   => 'Opera 4.x',
        'opera5up' => 'Opera 5.x',
        'nav'      => 'Netscape Navigator',
        'ns4'      => 'Netscape 4.x',
        'ns6up'    => 'Mozilla/Netscape 6.x',
        'konq'     => 'Konqueror',
    ))
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        foreach((array) $in_vendorStrings as $flag => $string) {
            if (!empty($this->browser[$flag])) {
                $vendorString = $string;
            }
        }

        // if there are no matches just use the user agent leading idendifier (usually Mozilla)
        if (!isset($vendorString)) {
            $vendorString = $this->leadingIdentifier;
        }
        
        return $vendorString;
    }

    // }}}
    // {{{ isIE()

    /**
     * Determine if the browser is an Internet Explorer browser
     *
     * @access public
     * @return bool whether or not this browser is an ie browser
     */
    function isIE()
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return !empty($this->browser['ie']);
    }

    // }}}
    // {{{ isNavigator()

    /**
     * Determine if the browser is a Netscape Navigator browser
     *
     * @access public
     * @return bool whether or not this browser is a Netscape Navigator browser
     */
    function isNavigator()
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return !empty($this->browser['nav']);
    }

    // }}}
    // {{{ isNetscape()

    /**
     * Determine if the browser is a Netscape or Mozilla browser
     *
     * Note that this function is not the same as isNavigator, since the
     * new Mozilla browsers are still sponsered by Netscape, and hence are
     * Netscape products, but not the original Navigators
     *
     * @access public
     * @return bool whether or not this browser is a Netscape product
     */
    function isNetscape()
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return !empty($this->browser['ns4up']);
    }
    
    // }}}
    // {{{ isOS()

    /**
     * Look up the provide OS flag and return a boolean value
     *
     * Given one of the flags listed in the properties, this function will return
     * the value associated with that flag for the operating system.
     *
     * @param  string $in_match flag to lookup
     *
     * @access public
     * @return boolean whether or not the OS satisfies this flag
     */
    function isOS($in_match)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $match = strtolower($in_match);
        return isset($this->os[$match]) ? $this->os[$match] : false;
    }

    // }}}
    // {{{ getOS()

    /**
     * Since simply returning the "os" is somewhat ambiguous since there
     * are different ways to classify the browser, this function works by taking
     * an expect list and returning the string of the first match, so put the important
     * ones first in the array.
     *
     * @access public
     * @return string first flag that matches
     */
    function getOS($in_expectList)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        foreach((array) $in_expectList as $os) {
            if (!empty($this->os[strtolower($os)])) {
                return $os;
            }
        }
    }

    // }}}
    // {{{ getOSString()

    /**
     * This function returns the os string corresponding to the flag.
     *
     * Either use the default matches or pass in an associative array of
     * flags and corresponding os strings.  This function will find
     * the highest version flag and return the os string corresponding
     * to the appropriate flag.  Be sure to pass in the flags in ascending order
     * if you want a basic matches first, followed by more detailed matches.
     *
     * @param  array $in_osStrings (optional) array of flags matched with os strings
     *
     * @access public
     * @return string os string matches appropriate flag
     */
    function getOSString($in_osStrings = array(
       'win'   => 'Microsoft Windows',
       'win9x' => 'Microsoft Windows 9x',
       'winme' => 'Microsoft Windows Millenium',
       'win2k' => 'Microsoft Windows 2000',
       'winnt' => 'Microsoft Windows NT',
       'mac'   => 'Macintosh',
       'unix'  => 'Linux/Unix',
    ))
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $osString = 'Unknown';

        foreach((array) $in_osStrings as $flag => $string) {
            if (!empty($this->os[$flag])) {
                $osString = $string;
            }
        }

        return $osString;
    }

    // }}}
    // {{{ setQuirk()

    /**
     * Set a unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @access public
     * @return void
     */
    function setQuirk($in_quirk, $in_hasQuirk = true)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $hasQuirk = !empty($in_hasQuirk); 
        $this->quirks[strtolower($in_quirk)] = $hasQuirk;
    }

    // }}}
    // {{{ hasQuirk()

    /**
     * Check a unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @access public
     * @return bool whether or not browser has this quirk
     */
    function hasQuirk($in_quirk)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return !empty($this->quirks[strtolower($in_quirk)]);
    }
    
    // }}}
    // {{{ getQuirk()

    /**
     * Get the unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @access public
     * @return string value of the quirk, in this case usually a boolean
     */
    function getQuirk()
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return isset($this->quirks[strtolower($in_quirks)]) ? $this->quirks[strtolower($in_quirks)] : null; 
    }

    // }}}
    // {{{ setFeature()

    /**
     * Set capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @access public
     * @return void
     */
    function setFeature($in_feature, $in_hasFeature = true)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $this->features[strtolower($in_feature)] = $in_hasFeature;
    }

    // }}}
    // {{{ hasFeature()

    /**
     * Check the capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @access public
     * @return bool whether or not the current client has this feature
     */
    function hasFeature($in_feature)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return !empty($this->features[strtolower($in_feature)]);
    }
    
    // }}}
    // {{{ getFeature()

    /**
     * Get the capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @access public
     * @return string value of the feature requested
     */
    function getFeature($in_feature)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return isset($this->features[strtolower($in_feature)]) ? $this->features[strtolower($in_feature)] : null; 
    }

    // }}}
    // {{{ getAcceptType()

    /**
     * Retrive the accept type for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function works like getBrowser() as it takes an expect list
     * and returns the first match.  For instance, to find the language
     * you would pass in your allowed languages and see if any of the
     * languages set in the browser match.
     *
     * @param  string $in_expectList values to check
     * @param  string $in_type type of accept
     *
     * @access public
     * @return string the first matched value
     */
    function getAcceptType($in_expectList, $in_type)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $type = strtolower($in_type);

        if ($type == 'mimetype' || $type == 'language' || $type == 'charset' || $type == 'encoding') {
            $typeArray =& $this->$type;
            foreach((array) $in_expectList as $match) {
                if (!empty($typeArray[$match])) {
                    return $match;
                }
            }
        }

        return null;
    }

    // }}}
    // {{{ setAcceptType()

    /**
     * Set the accept types for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function takes and array of accepted values for the type and
     * records them for retrieval.
     *
     * @param  array $in_values values of the accept type
     * @param  string $in_type type of accept
     *
     * @access public
     * @return void
     */
    function setAcceptType($in_values, $in_type)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $type = strtolower($in_type);

        if ($type == 'mimetype' || $type == 'language' || $type == 'charset' || $type == 'encoding') {
            $typeArray =& $this->$type;
            foreach((array) $in_values as $value) {
                $typeArray[$value] = true;
            }
        }
    }

    // }}}
    // {{{ hasAcceptType()

    /**
     * Check the accept types for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function checks the array for the given type and determines if
     * the browser accepts it.
     *
     * @param  string $in_value values to check
     * @param  string $in_type type of accept
     *
     * @access public
     * @return bool whether or not the value is accept for this type
     */
    function hasAcceptType($in_value, $in_type)
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        $type = strtolower($in_type);

        if ($type == 'mimetype' || $type == 'language' || $type == 'charset' || $type == 'encoding') {
            $typeArray =& $this->$type;
            return !empty($typeArray[$in_value]);
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ getUserAgent()

    /**
     * Return the user agent string that is being worked on
     *
     * @access public
     * @return string user agent
     */
    function getUserAgent()
    {
        if (!isset($this) || get_class($this) != 'net_useragent_detect') {
            $this =& Net_UserAgent_Detect::singleton();
        }

        return $this->userAgent;
    }

    // }}}
}
?>