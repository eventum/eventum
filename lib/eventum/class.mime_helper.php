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

/**
 * The MIME:: class provides methods for dealing with MIME standards.
 *
 * $Horde: horde/lib/MIME.php,v 1.121 2003/11/06 15:26:17 chuck Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
use Eventum\Mail\MailMessage;
use Eventum\Monolog\Logger;

/**
 * Class to handle the business logic related to the MIME email
 * processing. The is8bit(), endode() and _encode() functions come from
 * the excellent Horde package at http://www.horde.org. These functions are
 * licensed under the LGPL, and Horde's copyright notice is available
 * above.
 */
class Mime_Helper
{
    /**
     * Returns the appropriate message body for a given MIME-based decoded
     * structure.
     *
     * @param   object $output The parsed message structure
     * @return  string The message body
     * @see     self::decode()
     */
    public static function getMessageBody(&$output)
    {
        $parts = [];
        self::parse_output($output, $parts);
        if (empty($parts)) {
            Logger::app()->debug('parse_output failed. Corrupted MIME in email?', ['output' => $output]);
            // we continue as if nothing happened until it's clear it's right check to do.
        }

        $str = '';
        $is_html = false;
        if (isset($parts['text'])) {
            $str = implode("\n\n", $parts['text']);
        } elseif (isset($parts['html'])) {
            $is_html = true;
            $str = implode("\n\n", $parts['html']);

            // hack for inotes to prevent content from being displayed all on one line.
            $str = str_replace('</DIV><DIV>', "\n", $str);
            $str = str_replace(['<br>', '<br />', '<BR>', '<BR />'], "\n", $str);
        }

        // XXX: do we also need to do something here about base64 encoding?
        if ($is_html) {
            $str = strip_tags($str);

            // convert html entities. this should be done after strip tags
            $str = html_entity_decode($str, ENT_QUOTES, APP_CHARSET);
        }

        return $str;
    }

    /**
     * @deprecated  use decodeQuotedPrintable
     */
    public static function fixEncoding($input)
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
            }
            $address = stripslashes($address);
            $first_part = substr($address, 0, strrpos($address, '<') - 1);
            $first_part = '"' . str_replace('"', '\"', ($first_part)) . '"';
            $second_part = substr($address, strrpos($address, '<'));
            $address = $first_part . ' ' . $second_part;
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
            }
            $address = stripslashes($address);
            $first_part = substr($address, 0, strrpos($address, '<') - 1);
            $second_part = substr($address, strrpos($address, '<'));
            $address = $first_part;
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
            preg_match('/(.*)<(.*)>/', $address, $matches);

            $qq = preg_replace_callback(
                '/([\x80-\xFF]|[\x21-\x2F]|[\xFC]|\[|\])/',
                function ($m) {
                    return '=' . strtoupper(dechex(ord(stripslashes($m[1]))));
                },
                $matches[1]
            );
            $address = '=?' . APP_CHARSET . '?Q?' .
                str_replace(' ', '_', trim(
                    $qq
                )) . '?= <' . $matches[2] . '>';

            return $address;
        }

        return self::quoteSender($address);
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
    public static function decodeAddress($address)
    {
        if (preg_match("/=\?.+\?Q\?(.+)\?= <(.+)>/i", $address, $matches)) {
            return str_replace('_', ' ', quoted_printable_decode($matches[1])) . ' <' . $matches[2] . '>';
        }

        return self::removeQuotes($address);
    }

    /**
     * Encode into a quoted printable encoded string.
     *
     * @param   string $string The string in APP_CHARSET encoding
     * @return  string encoded string
     */
    public static function encodeQuotedPrintable($string)
    {
        // avoid any wrapping by specifying line length long enough
        // "test" -> 4
        // ": =?ISO-8859-1?B?dGVzdA==?=" -> 27
        // 3 +2 +10      +3 +7     + 3
        $line_length = strlen($string) * 4 + strlen(APP_CHARSET) + 11;

        $params = [
            'input-charset' => APP_CHARSET,
            'output-charset' => APP_CHARSET,
            'line-length' => $line_length,
        ];
        $string = iconv_mime_encode('', $string, $params);

        return substr($string, 2);
    }

    /**
     * Decode a quoted printable encoded string.
     *
     * Formerly known as 'fixEncoding' method in Eventum
     *
     * @author Elan Ruusamäe <glen@delfi.ee>
     * @see    Zend_Mime_Decode::decodeQuotedPrintable
     * @param  string $string encoded string
     * @return string The decoded string in APP_CHARSET encoding
     */
    public static function decodeQuotedPrintable($string)
    {
        // skip if not encoded, iconv_mime_decode otherwise removes unknown chars.
        // ideally this should not be needed, but we have places where we call this function twice.
        // TODO: log and remove duplicate calls (to same data) to decodeQuotedPrintable
        // TODO: use self::isQuotedPrintable if it is improved
        if (!preg_match("/=\?(?P<charset>.*?)\?(?P<scheme>[QB])\?(?P<string>.*?)\?=/i", $string)) {
            return $string;
        }

        return iconv_mime_decode($string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, APP_CHARSET);
    }

    /**
     * Returns if a specified string contains a quoted printable address.
     * TODO: make it support any parameter not just email address
     *
     * @param   string $address The email address
     * @return  bool if the address is quoted printable encoded
     */
    public static function isQuotedPrintable($address)
    {
        if (preg_match("/=\?.+\?Q\?.+\?= <.+>/i", $address)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a string contains 8-bit characters.
     *
     * @param string $string  the string to check
     * @return bool  true if it does, false if it doesn't
     */
    public static function is8bit($string)
    {
        if (is_string($string) && preg_match('/[\x80-\xff]+/', $string)) {
            return true;
        }

        return false;
    }

    public static function encodeHeaders($headers)
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
     * @param string $text     the text to encode
     * @param string $charset  (optional) The character set of the text
     * @return string  the text, encoded only if it contains non-ASCII
     *                 characters
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
     * @param string $text     the text to encode
     * @param string $charset  the character set of the text
     * @return string  the text, encoded only if it contains non-ASCII
     *                 characters
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
        }

        return '=?' . $charset . '?b?' . trim(base64_encode($text)) . '?=';
    }

    /**
     * Method used to encode a given string in the quoted-printable standard.
     *
     * @param   string $hdr_value The string to be encoded
     * @param   string $charset The charset of the string
     * @return  string The encoded string
     */
    public static function encodeValue($hdr_value, $charset = APP_CHARSET)
    {
        preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $hdr_value, $matches);
        $cb = function ($m) {
            return '=' . strtoupper(dechex(ord($m[1])));
        };
        foreach ($matches[1] as $value) {
            $replacement = preg_replace_callback('/([\x80-\xFF])/', $cb, $value);
            $hdr_value = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $hdr_value);
        }

        return $hdr_value;
    }

    /**
     * Given a string containing a header and body
     * section, this function will split them (at the first
     * blank line) and return them.
     *
     * @param   string $input Input to split apart
     * @return  array Contains header and body section
     */
    public static function splitBodyHeader($input)
    {
        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $input, $match)) {
            return [$match[1], $match[2]];
        }

        return null;
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
            if (strstr($first_part, '-')) {
                // if so, gotta get the number and increment it
                $numeric_portion = substr($first_part, strrpos($first_part, '-') + 1);
                if (preg_match('/^[0-9]+$/', $numeric_portion)) {
                    $numeric_portion = intval($numeric_portion) + 1;
                }
                $first_part = substr($first_part, 0, strrpos($first_part, '-'));
            } else {
                $numeric_portion = 1;
            }
            if (!strstr($filename, '.')) {
                $filename = $first_part . '-' . $numeric_portion;
            } else {
                $filename = $first_part . '-' . $numeric_portion . substr($filename, strrpos($filename, '.'));
            }

            return self::getAttachmentName($list, $filename);
        }

        return $filename;
    }

    /**
     * Method used to check whether a given email message has any attachments.
     *
     * @param string $message the full body of the message
     * @return  bool
     * @deprecated use MailMessage directly
     */
    public static function hasAttachments($message)
    {
        $mail = MailMessage::createFromString($message);

        return $mail->hasAttachments();
    }

    /**
     * Method used to decode the content of a MIME encoded message.
     *
     * @param   string $message The full body of the message
     * @param   bool $include_bodies Whether to include the bodies in the return value or not
     * @return  mixed The decoded content of the message
     */
    public static function decode(&$message, $include_bodies = false, $decode_bodies = true)
    {
        // need to fix a pretty annoying bug where if the 'boundary' part of a
        // content-type header is split into another line, the PEAR library would
        // not work correctly. this fix will make the boundary part go to the
        // same line as the content-type one
        if (preg_match('/^(boundary=).*/m', $message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $message = preg_replace($pattern, $replacement, $message);
        }

        $params = [
            'crlf' => "\r\n",
            'include_bodies' => $include_bodies,
            'decode_headers' => false,
            'decode_bodies' => $decode_bodies,
        ];
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
    public static function convertString($string, $source_charset)
    {
        if ($source_charset == false || $source_charset == APP_CHARSET) {
            return $string;
        }
        $res = iconv($source_charset, APP_CHARSET, $string);

        return $res === false ? $string : $res;
    }

    /**
     * Method used to parse the decoded object structure of a MIME
     * message into something more manageable.
     *
     * @param   object $obj The decoded object structure of the MIME message
     * @param   array $parts The parsed parts of the MIME message
     */
    public static function parse_output($obj, &$parts)
    {
        if (!empty($obj->parts)) {
            foreach ($obj->parts as &$part) {
                self::parse_output($part, $parts);
            }

            return;
        }

        $ctype = @strtolower($obj->ctype_primary . '/' . $obj->ctype_secondary);
        switch ($ctype) {
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
                } elseif (strtolower(@$obj->disposition) == 'attachment') {
                    @$parts['attachments'][] = $obj->body;
                } else {
                    @$parts['text'][] = $obj->body;
                }
        }
    }

    /**
     * Returns the internal list of content types that we do not support as
     * valid attachment types.
     *
     * @return string[] The list of content types
     */
    private static function _getInvalidContentTypes()
    {
        return [
            'message/rfc822',
            'application/pgp-signature',
            'application/ms-tnef',
        ];
    }

    /**
     * Returns the internal list of attachment dispositions that we do not
     * support as valid attachment types.
     *
     * @return string[] The list of valid dispositions
     */
    private static function _getValidDispositions()
    {
        return [
            'attachment',
            'inline',
        ];
    }

    /**
     * Splits the full email into headers and body
     *
     * @param   string $message The full email message
     * @param   bool $unfold If headers should be unfolded
     * @return  array An array containing the headers and body
     */
    public static function splitHeaderBody($message, $unfold = true)
    {
        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $message, $match)) {
            return [($unfold) ? Mail_Helper::unfold($match[1]) : $match[1], $match[2]];
        }

        return [];
    }

    /**
     * Initial implementation of flowed body handling per RFC 3676. This is probably
     * not complete but is a start.
     *
     * @see     http://www.faqs.org/rfcs/rfc3676.html
     * @param   string $body The text to "unflow"
     * @param   string $delsp If spaces should be deleted
     * @return  string The decoded body
     */
    public static function decodeFlowedBodies($body, $delsp)
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
