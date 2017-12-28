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

class Mime_Helper
{
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
     * @return  string The properly encoded email address: =?UTF-8?Q?Elan_Ruusam=C3=A4e?= <glen@example.com>
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

    /**
     * Encode a string containing non-ASCII characters according to RFC 2047.
     *
     * @param string $text     the text to encode
     * @param string $charset  (optional) The character set of the text
     * @return string  the text, encoded only if it contains non-ASCII
     *                 characters. Example: =?utf-8?b?WmXDpMOkbmQ=?=
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
