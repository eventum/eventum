<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.class.mime_helper.php 1.23 04/01/21 22:49:54-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the MIME email 
 * processing. The is8bit(), endode() and _encode() functions come from
 * the excellent Horde package at http://www.horde.org. These functions are
 * licensed under the LGPL, and Horde's copyright notice is available
 * below.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

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
* @author  Chuck Hagenbuch <chuck@horde.org>
* @version $Revision: 1.122 $
* @since   Horde 1.3
* @package Horde_MIME
*/

include_once(APP_PEAR_PATH . "Mail/mimeDecode.php");

class Mime_Helper
{
    /**
     * Returns the appropriate message body for a given MIME-based decoded
     * structure.
     *
     * @access  public
     * @param   object $output The parsed message structure
     * @return  string The message body
     * @see     Mime_Helper::decode()
     */
    function getMessageBody(&$output)
    {
        $parts = array();
        Mime_Helper::parse_output($output, $parts);
        $str = '';
        $is_html = false;
        if (isset($parts["text"])) {
            $str = $parts["text"][0];
        } elseif (isset($parts["html"])) {
            $is_html = true;
            $str = $parts["html"][0];
        }
        if (@$output->headers['content-transfer-encoding'] == 'quoted-printable') {
            $str = Mime_Helper::decodeBody($str, 'quoted-printable');
        }
        // XXX: do we also need to do something here about base64 encoding?
        if ($is_html) {
            $str = strip_tags($str);
        }
        return $str;
    }


    /**
     * Method used to fix the encoding of MIME based strings.
     *
     * @access  public
     * @param   string $input The string to be fixed
     * @return  string The fixed string
     */
    function fixEncoding($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];
            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;
                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }
            $input = str_replace($encoded, $text, $input);
        }
        return $input;
    }


    /**
     * Method used to properly quote the sender of a given email address.
     *
     * @access  public
     * @param   string $address The full email address
     * @return  string The properly quoted email address
     */
    function quoteSender($address)
    {
        if (strstr($address, '<')) {
            $address = stripslashes($address);
            $first_part = substr($address, 0, strrpos($address, '<') - 1);
            $first_part = '"' . $first_part . '"';
            $second_part = substr($address, strrpos($address, '<'));
            $address = $first_part . ' ' . $second_part;
        }
        return $address;
    }


    /**
     * Method used to remove any unnecessary quoting from an email address.
     *
     * @access  public
     * @param   string $address The full email address
     * @return  string The email address without quotes
     */
    function removeQuotes($address)
    {
        if (strstr($address, '<')) {
            $address = stripslashes($address);
            $first_part = substr($address, 0, strrpos($address, '<') - 1);
            $second_part = substr($address, strrpos($address, '<'));
            $address = $first_part;
        }
        if (preg_match('/^"(.*)"/', $address)) {
            $address = preg_replace('/^"(.*)"/', '\\1', $address);
        }
        if (!empty($second_part)) {
            $address .= ' ' . $second_part;
        }
        return $address;
    }


    /**
     * Method used to properly encode an email address.
     *
     * @access  public
     * @param   string $address The full email address
     * @return  string The properly encoded email address
     */
    function encodeAddress($address)
    {
        $address = MIME_Helper::removeQuotes($address);
        $address = MIME_Helper::encode($address);
        return MIME_Helper::quoteSender($address);
    }


    /**
     * Determine if a string contains 8-bit characters.
     *
     * @access public
     *
     * @param string $string  The string to check.
     *
     * @return boolean  True if it does, false if it doesn't.
     */
    function is8bit($string)
    {
        if (is_string($string) && preg_match('/[\x80-\xff]+/', $string)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Encode a string containing non-ASCII characters according to RFC 2047.
     *
     * @access public
     *
     * @param string $text     The text to encode.
     * @param string $charset  (optional) The character set of the text.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    function encode($text, $charset = 'iso-8859-1')
    {
        /* Return if nothing needs to be encoded. */
        if (!MIME_Helper::is8bit($text)) {
            return $text;
        }

        $charset = strtolower($charset);
        $line = '';

        /* Get the list of elements in the string. */
        $size = preg_match_all("/([^\s]+)([\s]*)/", $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $key => $val) {
            if (MIME_Helper::is8bit($val[1])) {
                if ((($key + 1) < $size) &&
                    MIME_Helper::is8bit($matches[$key + 1][1])) {
                    $line .= MIME_Helper::_encode($val[1] . $val[2], $charset) . ' ';
                } else {
                    $line .= MIME_Helper::_encode($val[1], $charset) . $val[2];
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
     * @access private
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    function _encode($text, $charset)
    {
        $char_len = strlen($charset);
        $txt_len = strlen($text) * 2;

        /* RFC 2047 [2] states that no encoded word can be more than 75
           characters long. If longer, you must split the word. */
        if (($txt_len + $char_len + 7) > 75) {
            $pos = intval((68 - $char_len) / 2);
            return MIME_Helper::_encode(substr($text, 0, $pos), $charset) . ' ' . MIME_Helper::_encode(substr($text, $pos), $charset);
        } else {
            return '=?' . $charset . '?b?' . trim(base64_encode($text)) . '?=';
        }
    }


    /**
     * Method used to encode a given string in the quoted-printable standard.
     *
     * @access  public
     * @param   string $hdr_value The string to be encoded
     * @return  string The encoded string
     */
    function encodeValue($hdr_value)
    {
        preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $hdr_value, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
            $hdr_value = str_replace($value, '=?iso-8859-1?Q?' . $replacement . '?=', $hdr_value);
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
        if ($input !== '') {
            // Unfold the input
            $input   = preg_replace("/\r?\n/", "\r\n", $input);
            $input   = preg_replace("/\r\n(\t| )+/", ' ', $input);
            $headers = explode("\r\n", trim($input));
            foreach ($headers as $value) {
                $hdr_name = substr($value, 0, $pos = strpos($value, ':'));
                $return[strtolower($hdr_name)] = $hdr_name;
            }
        } else {
            $return = array();
        }
        return $return;
    }


    /**
     * Method used to get an unique attachment name for a given
     * filename. This is specially useful for the emails that Microsoft
     * Outlook sends out with several attachments with the same name
     * when you embed several inline screenshots in the message
     *
     * @access  public
     * @param   array $list The nested array of mime parts
     * @param   string $filename The filename to search for
     * @return  string The unique attachment name
     */
    function getAttachmentName(&$list, $filename)
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
            return MIME_Helper::getAttachmentName($list, $filename);
        } else {
            return $filename;
        }
    }


    /**
     * Method used to parse and return the full list of attachments 
     * associated with a message.
     *
     * @access  public
     * @param   string $message The full body of the message
     * @return  array The list of attachments, if any
     */
    function getAttachments($message)
    {
        $parts = array();
        $output = Mime_Helper::decode($message, true);
        Mime_Helper::parse_output($output, $parts);
        $attachments = array();
        $filenames = array();
        for ($i = 0; $i < @count($output->parts); $i++) {
            // hack in order to display in-line images
            $bmp_filetypes = array('bmp', 'x-bmp');
            if ((@$output->parts[$i]->ctype_primary == 'image') && (@in_array($output->parts[$i]->ctype_secondary, $bmp_filetypes))) {
                $name = MIME_Helper::getAttachmentName($filenames, @$output->parts[$i]->ctype_parameters['name']);
                $filenames[] = $name;
                $attachments[] = array(
                    'filename' => $name,
                    'filetype' => 'image/' . $output->parts[$i]->ctype_secondary,
                    'blob'     => @$output->parts[$i]->body
                );
            } else {
                $filetype = @$output->parts[$i]->ctype_primary . '/' . @$output->parts[$i]->ctype_secondary;
                if ($filetype == '/') {
                    $filetype = '';
                }
                if ((!in_array(strtolower($filetype), Mime_Helper::_getInvalidContentTypes())) &&
                        (in_array(@strtolower($output->parts[$i]->disposition), Mime_Helper::_getValidDispositions())) && 
                        (!empty($output->parts[$i]->d_parameters["filename"]))) {
                    $name = MIME_Helper::getAttachmentName($filenames, @$output->parts[$i]->d_parameters["filename"]);
                    $filenames[] = $name;
                    $attachments[] = array(
                        'filename' => $name,
                        'filetype' => $filetype,
                        'blob'     => @$output->parts[$i]->body
                    );
                }
            }
        }
        return $attachments;
    }


    /**
     * Method used to parse and return the full list of attachment CIDs 
     * associated with a message.
     *
     * @access  public
     * @param   string $message The full body of the message
     * @return  array The list of attachment CIDs, if any
     */
    function getAttachmentCIDs($message)
    {
        // gotta parse MIME based emails now
        $output = Mime_Helper::decode($message, true);
        $attachments = array();
        // now get any eventual attachments
        for ($i = 0; $i < @count($output->parts); $i++) {
            // hack in order to display in-line images
            $bmp_filetypes = array('bmp', 'x-bmp');
            if ((@$output->parts[$i]->ctype_primary == 'image') &&
                    (@in_array($output->parts[$i]->ctype_secondary, $bmp_filetypes))) {
                $attachments[] = array(
                    'filename' => $output->parts[$i]->ctype_parameters['name'],
                    'cid'      => @$output->parts[$i]->headers['content-id']
                );
                continue;
            }
            if ((!in_array(strtolower($filetype), Mime_Helper::_getInvalidContentTypes())) &&
                    (in_array(@strtolower($output->parts[$i]->disposition), Mime_Helper::_getValidDispositions())) && 
                    (!empty($output->parts[$i]->d_parameters["filename"]))) {
                $attachments[] = array(
                    'filename' => $output->parts[$i]->d_parameters["filename"]
                );
                continue;
            }
        }
        return $attachments;
    }


    /**
     * Method used to get the encoded content of a specific message
     * attachment.
     *
     * @access  public
     * @param   string $message The full content of the message
     * @param   string $filename The filename to look for
     * @param   string $cid The content-id to look for, if any
     * @return  string The full encoded content of the attachment
     */
    function getAttachment($message, $filename, $cid = FALSE)
    {
        $parts = array();
        $output = Mime_Helper::decode($message, true);
        Mime_Helper::parse_output($output, $parts);
        for ($i = 0; $i < count($output->parts); $i++) {
            if ($cid !== FALSE) {
                // hack in order to display in-line images
                $bmp_filetypes = array('bmp', 'x-bmp');
                if ((@$output->parts[$i]->ctype_primary == 'image') &&
                        (@in_array($output->parts[$i]->ctype_secondary, $bmp_filetypes)) &&
                        (@$output->parts[$i]->ctype_parameters['name'] == $filename) &&
                        (@$output->parts[$i]->headers['content-id'] == $cid)) {
                    break;
                }
            } else {
                if ((!in_array(strtolower($filetype), Mime_Helper::_getInvalidContentTypes())) &&
                        (in_array(@strtolower($output->parts[$i]->disposition), Mime_Helper::_getValidDispositions())) && 
                        (!empty($output->parts[$i]->d_parameters["filename"])) &&
                        (@$output->parts[$i]->d_parameters["filename"] == $filename)) {
                    break;
                }
            }
        }
        return array(
            $output->parts[$i]->ctype_primary . '/' . $output->parts[$i]->ctype_secondary,
            $output->parts[$i]->body
        );
    }


    /**
     * Method used to decode the content of a MIME encoded message.
     *
     * @access  public
     * @param   string $message The full body of the message
     * @param   boolean $include_bodies Whether to include the bodies in the return value or not
     * @return  mixed The decoded content of the message
     */
    function decode($message, $include_bodies = FALSE, $decode_bodies = TRUE)
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
            'decode_headers' => TRUE,
            'decode_bodies'  => $decode_bodies
        );
        $decode = new Mail_mimeDecode($message);
        return $decode->decode($params);
    }


    /**
     * Method used to parse the decoded object structure of a MIME
     * message into something more manageable.
     *
     * @access  public
     * @param   object $obj The decoded object structure of the MIME message
     * @param   array $parts The parsed parts of the MIME message
     * @return  array List of parts that exist in the MIME message
     */
    function parse_output(&$obj, &$parts)
    {
        if (!empty($obj->parts)) {
            for ($i = 0; $i < count($obj->parts); $i++) {
                Mime_Helper::parse_output($obj->parts[$i], $parts);
            }
        } else {
            $ctype = @strtolower($obj->ctype_primary.'/'.$obj->ctype_secondary);
            switch($ctype){
                case 'text/plain':
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['text'][] = $obj->body;
                    }
                    break;
                case 'text/html':
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['html'][] = $obj->body;
                    }
                    break;
                // special case for Apple Mail
                case 'text/enriched':
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'attachment')) {
                        @$parts['attachments'][] = $obj->body;
                    } else {
                        @$parts['html'][] = $obj->body;
                    }
                    break;
                default:
                    // avoid treating forwarded messages as attachments
                    if ((!empty($obj->disposition)) && (strtolower($obj->disposition) == 'inline') &&
                            ($ctype != 'message/rfc822')) {
                        @$parts['attachments'][] = $obj->body;
                    }
            }
        }
    }


    /**
     * Method used to decode the body of a MIME encoded message.
     *
     * @access  public
     * @param   string $input The full body of the message
     * @param   string $encoding The encoding used in the message
     * @return  string The decoded message body
     */
    function decodeBody($input, $encoding = '7bit')
    {
        switch ($encoding) {
            case '7bit':
                return $input;
                break;

            case 'quoted-printable':
                return Mime_Helper::_quotedPrintableDecode($input);
                break;

            case 'base64':
                return base64_decode($input);
                break;

            default:
                return $input;
        }
    }


    /**
     * Given a quoted-printable string, this
     * function will decode and return it.
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
            'message/rfc822'
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
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Mime_Helper Class');
}
?>