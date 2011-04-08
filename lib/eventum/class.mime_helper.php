<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//

/**
* The MIME:: class provides methods for dealing with MIME standards.
*
* $Horde: horde/lib/MIME.php,v 1.121 2003/11/06 15:26:17 chuck Exp $
*
* Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
*
* See the enclosed file COPYING for license information (LGPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
*
*/

require_once 'Mail/mimeDecode.php';

/**
 * Class to handle the business logic related to the MIME email
 * processing. The is8bit(), endode() and _encode() functions come from
 * the excellent Horde package at http://www.horde.org. These functions are
 * licensed under the LGPL, and Horde's copyright notice is available
 * above.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */
class Mime_Helper
{
    /**
     * Method used to get charset from raw email.
     *
     * @access  public
     * @param   mixed   $input The full body of the message or decoded email.
     * @return  string charset extracted from Content-Type header of email.
     */
    function getCharacterSet($input)
    {
        if (!is_object($input)) {
            $structure = self::decode($input, false, false);
        } else {
            $structure = $input;
        }
        if (empty($structure)) {
            return false;
        }

        if ($structure->ctype_primary == 'multipart' and $structure->ctype_secondary == 'mixed'
            and count($structure->parts) >= 1 and $structure->parts[0]->ctype_primary == 'text') {
            $content_type = $structure->parts[0]->headers['content-type'];
        } else {
            $content_type = !empty($structure->headers['content-type']) ? $structure->headers['content-type'] : '';
        }

        if (preg_match('/charset\s*=\s*(["\'])?([-\w\d]+)(\1)?;?/i', $content_type, $matches)) {
            return $matches[2];
        }

        return false;
    }


    /**
     * Returns the appropriate message body for a given MIME-based decoded
     * structure.
     *
     * @access  public
     * @param   object $output The parsed message structure
     * @return  string The message body
     * @see     self::decode()
     */
    function getMessageBody(&$output)
    {
        $parts = array();
        self::parse_output($output, $parts);
        if (empty($parts)) {
            Error_Handler::logError(array("self::parse_output failed. Corrupted MIME in email?", $output), __FILE__, __LINE__);
            // we continue as if nothing happened until it's clear it's right check to do.
        }
        $str = '';
        $is_html = false;
        if (isset($parts["text"])) {
            $str = join("\n\n", $parts["text"]);
        } elseif (isset($parts["html"])) {
            $is_html = true;
            $str = join("\n\n", $parts["html"]);

            // hack for inotes to prevent content from being displayed all on one line.
            $str = str_replace("</DIV><DIV>", "\n", $str);
            $str = str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $str);
        }
        // XXX: do we also need to do something here about base64 encoding?
        if ($is_html) {
            $str = strip_tags($str);
        }
        return $str;
    }

    /**
     * @deprecated  use decodeQuotedPrintable
     */
    function fixEncoding($input)
    {
        return self::decodeQuotedPrintable($input);
    }

    /**
     * Method used to properly quote the sender of a given email address.
     *
     * @param   string $address The full email address
     * @return  string The properly quoted email address
     */
    public static function quoteSender($address)
    {
        if (strstr($address, '<')) {
            if (substr($address, 0, 1) == '<') {
                return substr($address, 1, -1);
            } else {
                $address = stripslashes($address);
                $first_part = substr($address, 0, strrpos($address, '<') - 1);
                $first_part = '"' . str_replace('"', '\"',($first_part)) . '"';
                $second_part = substr($address, strrpos($address, '<'));
                $address = $first_part . ' ' . $second_part;
            }
        }
        return $address;
    }


    /**
     * Method used to remove any unnecessary quoting from an email address.
     *
     * @param   string $address The full email address
     * @return  string The email address without quotes
     */
    public static function removeQuotes($address)
    {
        if (strstr($address, '<')) {
            if (substr($address, 0, 1) == '<') {
                return substr($address, 1, -1);
            } else {
                $address = stripslashes($address);
                $first_part = substr($address, 0, strrpos($address, '<') - 1);
                $second_part = substr($address, strrpos($address, '<'));
                $address = $first_part;
            }
        }
        if (preg_match('/^".*"/', $address)) {
            $address = preg_replace('/^"(.*)"/', '\\1', $address);
        }
        if (isset($second_part) && !empty($second_part)) {
            $address .= ' ' . $second_part;
        }
        return $address;
    }


    /**
     * Method used to properly encode an email address.
     *
     * @param   string $address The full email address
     * @return  string The properly encoded email address
     */
    public static function encodeAddress($address)
    {
        $address = self::removeQuotes($address);
        if (self::is8bit($address)) {
            // split into name and address section
            preg_match("/(.*)<(.*)>/", $address, $matches);
           $address = "=?" . APP_CHARSET . "?Q?" .
                str_replace(' ', '_', trim(preg_replace('/([\x80-\xFF]|[\x21-\x2F]|[\xFC]|\[|\])/e', '"=" . strtoupper(dechex(ord(stripslashes("\1"))))', $matches[1]))) . "?= <" . $matches[2] . ">";
           return $address;
        } else {
            return self::quoteSender($address);
        }
    }


    /**
     * Decodes a quoted printable encoded address and returns the string.
     *
     * FIXME: it does not respect charset being used in qp string
     * use self::decode() instead.
     *
     * @param   string $address The address to decode
     * @return  string The decoded address
     */
    function decodeAddress($address)
    {
        if (preg_match("/=\?.+\?Q\?(.+)\?= <(.+)>/i", $address, $matches)) {
            return str_replace("_", ' ', quoted_printable_decode($matches[1])) . " <" . $matches[2] . ">";
        } else {
            return self::removeQuotes($address);
        }
    }

    /**
     * Encode into a quoted printable encoded string.
     *
     * @author Elan Ruusamäe <glen@delfi.ee>
     * @see    Zend_Mime::_encodeQuotedPrintable
     * @param   string The string in APP_CHARSET encoding
     * @return  string encoded string
     */

    public static function encodeQuotedPrintable($string)
    {
        if (function_exists('iconv_mime_encode')) {
            // avoid any wrapping by specifying line length long enough
            // "test" -> 4
            // ": =?ISO-8859-1?B?dGVzdA==?=" -> 27
            // 3 +2 +10      +3 +7     + 3
            $line_length = strlen($string) * 4 + strlen(APP_CHARSET) + 11;

            $params = array(
                "input-charset" => APP_CHARSET,
                "output-charset" => APP_CHARSET,
                "line-length" => $line_length,
            );
            $string = iconv_mime_encode("", $string, $params);
            return substr($string, 2);
        }

        // lookup-Tables for QuotedPrintable
        $qpKeys = array(
            "\x00","\x01","\x02","\x03","\x04","\x05","\x06","\x07",
            "\x08","\x09","\x0A","\x0B","\x0C","\x0D","\x0E","\x0F",
            "\x10","\x11","\x12","\x13","\x14","\x15","\x16","\x17",
            "\x18","\x19","\x1A","\x1B","\x1C","\x1D","\x1E","\x1F",
            "\x7F","\x80","\x81","\x82","\x83","\x84","\x85","\x86",
            "\x87","\x88","\x89","\x8A","\x8B","\x8C","\x8D","\x8E",
            "\x8F","\x90","\x91","\x92","\x93","\x94","\x95","\x96",
            "\x97","\x98","\x99","\x9A","\x9B","\x9C","\x9D","\x9E",
            "\x9F","\xA0","\xA1","\xA2","\xA3","\xA4","\xA5","\xA6",
            "\xA7","\xA8","\xA9","\xAA","\xAB","\xAC","\xAD","\xAE",
            "\xAF","\xB0","\xB1","\xB2","\xB3","\xB4","\xB5","\xB6",
            "\xB7","\xB8","\xB9","\xBA","\xBB","\xBC","\xBD","\xBE",
            "\xBF","\xC0","\xC1","\xC2","\xC3","\xC4","\xC5","\xC6",
            "\xC7","\xC8","\xC9","\xCA","\xCB","\xCC","\xCD","\xCE",
            "\xCF","\xD0","\xD1","\xD2","\xD3","\xD4","\xD5","\xD6",
            "\xD7","\xD8","\xD9","\xDA","\xDB","\xDC","\xDD","\xDE",
            "\xDF","\xE0","\xE1","\xE2","\xE3","\xE4","\xE5","\xE6",
            "\xE7","\xE8","\xE9","\xEA","\xEB","\xEC","\xED","\xEE",
            "\xEF","\xF0","\xF1","\xF2","\xF3","\xF4","\xF5","\xF6",
            "\xF7","\xF8","\xF9","\xFA","\xFB","\xFC","\xFD","\xFE",
            "\xFF"
        );

        $qpReplaceValues = array(
            "=00","=01","=02","=03","=04","=05","=06","=07",
            "=08","=09","=0A","=0B","=0C","=0D","=0E","=0F",
            "=10","=11","=12","=13","=14","=15","=16","=17",
            "=18","=19","=1A","=1B","=1C","=1D","=1E","=1F",
            "=7F","=80","=81","=82","=83","=84","=85","=86",
            "=87","=88","=89","=8A","=8B","=8C","=8D","=8E",
            "=8F","=90","=91","=92","=93","=94","=95","=96",
            "=97","=98","=99","=9A","=9B","=9C","=9D","=9E",
            "=9F","=A0","=A1","=A2","=A3","=A4","=A5","=A6",
            "=A7","=A8","=A9","=AA","=AB","=AC","=AD","=AE",
            "=AF","=B0","=B1","=B2","=B3","=B4","=B5","=B6",
            "=B7","=B8","=B9","=BA","=BB","=BC","=BD","=BE",
            "=BF","=C0","=C1","=C2","=C3","=C4","=C5","=C6",
            "=C7","=C8","=C9","=CA","=CB","=CC","=CD","=CE",
            "=CF","=D0","=D1","=D2","=D3","=D4","=D5","=D6",
            "=D7","=D8","=D9","=DA","=DB","=DC","=DD","=DE",
            "=DF","=E0","=E1","=E2","=E3","=E4","=E5","=E6",
            "=E7","=E8","=E9","=EA","=EB","=EC","=ED","=EE",
            "=EF","=F0","=F1","=F2","=F3","=F4","=F5","=F6",
            "=F7","=F8","=F9","=FA","=FB","=FC","=FD","=FE",
            "=FF"
        );

        $string = str_replace('=', '=3D', $string);
        $string = str_replace($qpKeys, $qpReplaceValues, $string);
        return rtrim($string);
    }

    /**
     * Decode a quoted printable encoded string.
     *
     * @author Elan Ruusamäe <glen@delfi.ee>
     * @see    Zend_Mime_Decode::decodeQuotedPrintable
     * @param  string encoded string
     * @return string The decoded string in APP_CHARSET encoding
     */
    public static function decodeQuotedPrintable($string)
    {
        if (function_exists('iconv_mime_decode')) {
            // skip if not encoded, iconv_mime_decode otherwise removes unknown chars.
            // ideally this should be needed, but we have places where we call this function twice.
            // TODO: log and remove duplicate calls (to same data) to decodeQuotedPrintable
            // TODO: use self::isQuotedPrintable if it is improved
            if (!preg_match("/=\?(?P<charset>.*?)\?(?P<scheme>[QB])\?(?P<string>.*?)\?=/i", $string)) {
                return $string;
            }
            return iconv_mime_decode($string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, APP_CHARSET);
        }

        // this part does not function properly if iconv extension is missing
        // +=?UTF-8?B?UHLDvGZ1bmcgUHLDvGZ1bmc=?=
        preg_match_all("/(?P<before>.*?)=\?(?P<charset>.*?)\?(?P<scheme>[QB])\?(?P<string>.*?)\?=(?P<after>.*?)/i", $string, $matches, PREG_SET_ORDER);
        $string = '';
        foreach ($matches as $m) {
            $string .= $m['before'];
            switch (strtolower($m['scheme'])) {
            case 'q':
                $s = quoted_printable_decode($m['string']);
                $s = str_replace('_', ' ', $s);
                break;
            case 'b':
                $s = base64_decode($m['string']);
                break;
            default:
                // unknown, leave undecoded
                $s = $m['string'];
            }
            if (function_exists('iconv')) {
                $string .= iconv($m['charset'], APP_CHARSET, $s);
            } else {
                $string .= $s;
            }
            $string .= $m['after'];
        }
        return $string;
    }


    /**
     * Returns if a specified string contains a quoted printable address.
     * TODO: make it support any parameter not just email address
     *
     * @param   string $address The email address
     * @return  boolean If the address is quoted printable encoded.
     */
    function isQuotedPrintable($address)
    {
        if (preg_match("/=\?.+\?Q\?.+\?= <.+>/i", $address)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Determine if a string contains 8-bit characters.
     *
     * @param string $string  The string to check.
     * @return boolean  True if it does, false if it doesn't.
     */
    public static function is8bit($string)
    {
        if (is_string($string) && preg_match('/[\x80-\xff]+/', $string)) {
            return true;
        } else {
            return false;
        }
    }


    function encodeHeaders($headers)
    {
        // encodes emails headers
        foreach ($headers as $name => $value) {
            $headers[$name] = self::encode($value);
        }
        return $headers;
    }


    /**
     * Encode a string containing non-ASCII characters according to RFC 2047.
     *
     * @param string $text     The text to encode.
     * @param string $charset  (optional) The character set of the text.
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    public static function encode($text, $charset = APP_CHARSET)
    {
        /* Return if nothing needs to be encoded. */
        if (!self::is8bit($text)) {
            return $text;
        }

        $charset = strtolower($charset);
        $line = '';

        /* Get the list of elements in the string. */
        $size = preg_match_all("/([^\s]+)([\s]*)/", $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $key => $val) {
            if (self::is8bit($val[1])) {
                if ((($key + 1) < $size) &&
                    self::is8bit($matches[$key + 1][1])) {
                    $line .= self::_encode($val[1] . $val[2], $charset) . ' ';
                } else {
                    $line .= self::_encode($val[1], $charset) . $val[2];
                }
            } else {
                $line .= $val[1] . $val[2];
            }
        }

        return rtrim($line);
    }

    /**
     * Internal recursive function to RFC 2047 encode a string.
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    private static function _encode($text, $charset)
    {
        $char_len = strlen($charset);
        $txt_len = strlen($text) * 2;

        /* RFC 2047 [2] states that no encoded word can be more than 75
           characters long. If longer, you must split the word. */
        if (($txt_len + $char_len + 7) > 75) {
            $pos = intval((68 - $char_len) / 2);
            return self::_encode(substr($text, 0, $pos), $charset) . ' ' . self::_encode(substr($text, $pos), $charset);
        } else {
            return '=?' . $charset . '?b?' . trim(base64_encode($text)) . '?=';
        }
    }


    /**
     * Method used to encode a given string in the quoted-printable standard.
     *
     * @access  public
     * @param   string $hdr_value The string to be encoded
     * @param   string $charset The charset of the string
     * @return  string The encoded string
     */
    function encodeValue($hdr_value, $charset = 'iso-8859-1')
    {
        preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $hdr_value, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
            $hdr_value = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $hdr_value);
        }
        return $hdr_value;
    }


    /**
     * Given a string containing a header and body
     * section, this function will split them (at the first
     * blank line) and return them.
     *
     * @access  public
     * @param   string $input Input to split apart
     * @return  array Contains header and body section
     */
    function splitBodyHeader($input)
    {
        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $input, $match)) {
            return array($match[1], $match[2]);
        }
    }


    /**
     * Parse headers given in $input and return
     * as assoc array.
     *
     * @access  public
     * @param   string $input Headers to parse
     * @return  array Contains parsed headers
     */
    function getHeaderNames($input)
    {
        if ($input === '') {
	        return array();
        }

		$return = array();
		// Unfold the input
		$input   = preg_replace("/\r?\n/", "\r\n", $input);
		$input   = preg_replace("/\r\n(\t| )+/", ' ', $input);
		$headers = explode("\r\n", trim($input));
		foreach ($headers as $value) {
			$hdr_name = substr($value, 0, strpos($value, ':'));
			$return[strtolower($hdr_name)] = $hdr_name;
		}
        return $return;
    }


    /**
     * Method used to get an unique attachment name for a given
     * filename. This is specially useful for the emails that Microsoft
     * Outlook sends out with several attachments with the same name
     * when you embed several inline screenshots in the message
     *
     * @param   array $list The nested array of mime parts
     * @param   string $filename The filename to search for
     * @return  string The unique attachment name
     */
    public static function getAttachmentName(&$list, $filename)
    {
        if (@in_array($filename, array_values($list))) {
            // check if the filename even has an extension...
            if (!strstr($filename, '.')) {
                $first_part = $filename;
            } else {
                $first_part = substr($filename, 0, strrpos($filename, '.'));
            }
            // check if this is already named Outlook-2.bmp (or similar)
            if (strstr($first_part, "-")) {
                // if so, gotta get the number and increment it
                $numeric_portion = substr($first_part, strrpos($first_part, "-")+1);
                if (preg_match("/^[0-9]+$/", $numeric_portion)) {
                    $numeric_portion = intval($numeric_portion) + 1;
                }
                $first_part = substr($first_part, 0, strrpos($first_part, "-"));
            } else {
                $numeric_portion = 1;
            }
            if (!strstr($filename, '.')) {
                $filename = $first_part . "-" . $numeric_portion;
            } else {
                $filename = $first_part . "-" . $numeric_portion . substr($filename, strrpos($filename, '.'));
            }
            return self::getAttachmentName($list, $filename);
        } else {
            return $filename;
        }
    }


    /**
     * Method used to check whether a given email message has any attachments.
     *
     * @access  public
     * @param   mixed   $message The full body of the message or parsed message structure.
     * @return  boolean
     */
    function hasAttachments($message)
    {
        if (!is_object($message)) {
            $message = self::decode($message, true);
        }
        $attachments = self::_getAttachmentDetails($message, true);
        if (count($attachments) > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to parse and return the full list of attachments
     * associated with a message.
     *
     * @access  public
     * @param   mixed   $message The full body of the message or parsed message structure.
     * @return  array The list of attachments, if any
     */
    function getAttachments($message)
    {
        if (!is_object($message)) {
            $message = self::decode($message, true);
        }
        return self::_getAttachmentDetails($message, true);
    }


    /**
     * Method used to parse and return the full list of attachment CIDs
     * associated with a message.
     *
     * @access  public
     * @param   mixed   $message The full body of the message or parsed message structure.
     * @return  array The list of attachment CIDs, if any
     */
    function getAttachmentCIDs($message)
    {
        if (!is_object($message)) {
            $message = self::decode($message, true);
        }
        return self::_getAttachmentDetails($message, true);
    }


    private static function _getAttachmentDetails(&$mime_part, $return_body = false, $return_filename = false, $return_cid = false)
    {
        $attachments = array();
        if (isset($mime_part->parts)) {
            for ($i = 0; $i < count($mime_part->parts); $i++) {
                $t = self::_getAttachmentDetails($mime_part->parts[$i], $return_body, $return_filename, $return_cid);
                $attachments = array_merge($t, $attachments);
            }
        }
        // FIXME: content-type is always lowered by PEAR class (CHECKME) and why not $mime_part->content_type?
        $content_type = strtolower(@$mime_part->ctype_primary . '/' . @$mime_part->ctype_secondary);
        if ($content_type == '/') {
            $content_type = '';
        }
        $found = 0;

        // attempt to extract filename
        $mime_part_filename = '';
        if (!empty($mime_part->ctype_parameters['name'])) {
            $mime_part_filename = self::decodeQuotedPrintable($mime_part->ctype_parameters['name']);
        }
        if (empty($mime_part_filename) && !empty($mime_part->d_parameters['filename'])) {
            $mime_part_filename = self::decodeQuotedPrintable($mime_part->d_parameters['filename']);
        }

        // hack in order to treat inline images as normal attachments
        // (since Eventum does not display those embedded within the message)
        if (isset($mime_part->ctype_primary ) && $mime_part->ctype_primary == 'image') {
            // if requested, return only the details of a particular filename
            if (($return_filename != false) && ($mime_part_filename != $return_filename)) {
                return array();
            }
            // if requested, return only the details of
            // a particular attachment CID. Only really needed
            // as hack for inline images
            if ($return_cid != false && (@$mime_part->headers['content-id'] != $return_cid)) {
                return array();
            }
            $found = 1;

            // inline images might not have filename
            if (empty($mime_part_filename)) {
                $ext = $mime_part->ctype_secondary;
                // TRANSLATORS: filename for inline image attachments, where %s is file extension
                $mime_part_filename = ev_gettext('Untitled.%s', $ext);
            }
        } else {
            if ((!in_array($content_type, self::_getInvalidContentTypes())) &&
                    (in_array(@strtolower($mime_part->disposition), self::_getValidDispositions())) &&
                    (!empty($mime_part_filename))) {
                // if requested, return only the details of a particular filename
                if (($return_filename != false) && ($mime_part_filename != $return_filename)) {
                    return array();
                }
                $found = 1;
            }
        }
        if ($found) {
            $t = array(
                'filename' => $mime_part_filename,
                'cid'      => @$mime_part->headers['content-id'],
                'filetype' => $content_type
            );
            // only include the body of the attachment when
            // requested to save some memory
            if ($return_body == true) {
                $t['blob'] = &$mime_part->body;
            }
            $attachments[] = $t;
        }

        return $attachments;
    }


    /**
     * Method used to get the encoded content of a specific message
     * attachment.
     *
     * @access  public
     * @param   mixed   $message The full content of the message or parsed message structure.
     * @param   string $filename The filename to look for
     * @param   string $cid The content-id to look for, if any
     * @return  string The full encoded content of the attachment
     */
    function getAttachment($message, $filename, $cid = false)
    {
        $parts = array();
        if (!is_object($message)) {
            $message = self::decode($message, true);
        }
        $details = self::_getAttachmentDetails($message, true, $filename, $cid);
        if (count($details) == 1) {
            return array(
                $details[0]['filetype'],
                $details[0]['blob']
            );
        } else {
            return array();
        }
    }


    /**
     * Method used to decode the content of a MIME encoded message.
     *
     * @access  public
     * @param   string $message The full body of the message
     * @param   boolean $include_bodies Whether to include the bodies in the return value or not
     * @return  mixed The decoded content of the message
     */
    public static function decode(&$message, $include_bodies = false, $decode_bodies = true)
    {
        // need to fix a pretty annoying bug where if the 'boundary' part of a
        // content-type header is split into another line, the PEAR library would
        // not work correctly. this fix will make the boundary part go to the
        // same line as the content-type one
        if (preg_match("/^(boundary=).*/m", $message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $message = preg_replace($pattern, $replacement, $message);
        }

        $params = array(
            'crlf'           => "\r\n",
            'include_bodies' => $include_bodies,
            'decode_headers' => false,
            'decode_bodies'  => $decode_bodies
        );
        $decode = new Mail_mimeDecode($message);
        $email = $decode->decode($params);

        foreach ($email->headers as $name => $value) {
            if (is_string($value)) {
                $email->headers[$name] = iconv_mime_decode(trim($value), ICONV_MIME_DECODE_CONTINUE_ON_ERROR, APP_CHARSET);
            }
        }
        if ($include_bodies) {
            $email->body = self::getMessageBody($email);
        }

        return $email;
    }


    /**
     * Converts a string from a specified charset to the application charset
     *
     * @param   string $string
     * @param   string $source_charset
     * @return  string The converted string
     */
    private static function convertString($string, $source_charset)
    {
        if (($source_charset == false) || ($source_charset == APP_CHARSET)) {
            return $string;
        } else {
            $res = iconv($source_charset, APP_CHARSET, $string);
            return $res === false ? $string : $res;
        }
    }

    /**
     * Method used to parse the decoded object structure of a MIME
     * message into something more manageable.
     *
     * @param   object $obj The decoded object structure of the MIME message
     * @param   array $parts The parsed parts of the MIME message
     * @return  void
     */
    public static function parse_output($obj, &$parts)
    {
        if (!empty($obj->parts)) {
            for ($i = 0; $i < count($obj->parts); $i++) {
                self::parse_output($obj->parts[$i], $parts);
            }
        } else {
            $ctype = @strtolower($obj->ctype_primary.'/'.$obj->ctype_secondary);
            switch($ctype){
                case 'text/plain':
                    if (((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) || (!empty($obj->d_parameters['filename']))) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        $text = self::convertString($obj->body, @$obj->ctype_parameters['charset']);
                        if (@$obj->ctype_parameters['format'] == 'flowed') {
                            $text = self::decodeFlowedBodies($text, @$obj->ctype_parameters['delsp']);
                        }
                        @$parts['text'][] = $text;
                    }
                    break;
                case 'text/html':
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['html'][] = self::convertString($obj->body, @$obj->ctype_parameters['charset']);
                    }
                    break;
                // special case for Apple Mail
                case 'text/enriched':
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['html'][] = self::convertString($obj->body, @$obj->ctype_parameters['charset']);
                    }
                    break;
                default:
                    // avoid treating forwarded messages as attachments
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'inline') &&
                            ($ctype != 'message/rfc822')) {
                        @$parts['attachments'][] = $obj->body;
                    } elseif (stristr($ctype, 'image')) {
                        // handle inline images
                        @$parts['attachments'][] = $obj->body;
                    } elseif(strtolower(@$obj->disposition) == 'attachment') {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['text'][] = $obj->body;
                    }
            }
        }
    }


    /**
     * Given a quoted-printable string, this
     * function will decode and return it.
     *
     * FIXME: it does not respect charset being used in qp string
     *
     * @access private
     * @param  string Input body to decode
     * @return string Decoded body
     */
    function _quotedPrintableDecode($input)
    {
        // Remove soft line breaks
        $input = preg_replace("/=\r?\n/", '', $input);

        // Replace encoded characters
        $input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);

        return $input;
    }


    /**
     * Returns the internal list of content types that we do not support as
     * valid attachment types.
     *
     * @access private
     * @return array The list of content types
     */
    function _getInvalidContentTypes()
    {
        return array(
            'message/rfc822',
            'application/pgp-signature',
            'application/ms-tnef',
        );
    }


    /**
     * Returns the internal list of attachment dispositions that we do not
     * support as valid attachment types.
     *
     * @access private
     * @return array The list of valid dispositions
     */
    function _getValidDispositions()
    {
        return array(
            'attachment',
            'inline'
        );
    }


    /**
     * Splits the full email into headers and body
     *
     * @access  public
     * @param   string $message The full email message
     * @param   boolean $unfold If headers should be unfolded
     * @return  array An array containing the headers and body
     */
    function splitHeaderBody($message, $unfold = true)
    {
        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $message, $match)) {
            return array(($unfold) ? Mail_Helper::unfold($match[1]) : $match[1], $match[2]);
        }
        return array();
    }



    /**
     * Initial implementation of flowed body handling per RFC 3676. This is probably
     * not complete but is a start.
     *
     * @see     http://www.faqs.org/rfcs/rfc3676.html
     * @param   text $body The text to "unflow"
     * @param   string $delsp If spaces should be deleted
     * @return  string The decoded body
     */
    function decodeFlowedBodies($body, $delsp)
    {
        if ($delsp == 'yes') {
            $delsp = true;
        } else {
            $delsp = false;
        }

        $lines = explode("\n", $body);

        $text = '';
        foreach ($lines as $line) {
            if (($line != '-- ') && (substr(Misc::removeNewLines($line, true), -1) == ' ')) {
                if ($delsp) {
                    $text .= substr(Misc::removeNewLines($line, true), 0, -1);
                } else {
                    $text .= Misc::removeNewLines($line, true);
                }
            } else {
                $text .= $line . "\n";
            }
        }
        return $text;
    }
}
