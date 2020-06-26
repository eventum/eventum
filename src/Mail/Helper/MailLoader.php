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

namespace Eventum\Mail\Helper;

use Laminas\Mail;
use Laminas\Mail\Header;
use Laminas\Mail\Headers;
use Laminas\Mime;
use Mime_Helper;

class MailLoader
{
    public static function splitMessage($raw, &$headers, &$content): void
    {
        // do our own header-body splitting.
        //
        // \Laminas\Mail\Storage\Message is unable to process mails that contain \n\n in text body
        // because it has heuristic which headers separator to use
        // and that gets out of control
        // https://github.com/zendframework/zend-mail/pull/159

        try {
            // use RFC compliant "\r\n" EOL
            Mime\Decode::splitMessage($raw, $headers, $content, "\r\n");
        } catch (Mail\Exception\InvalidArgumentException $e) {
            // retry with heuristic
            try {
                Mime\Decode::splitMessage($raw, $headers, $content);
            } catch (Mail\Exception\InvalidArgumentException $e) {
                static::fallbackMessageSplit($raw, $headers, $content);
            }
        } catch (Mail\Exception\RuntimeException $e) {
            // retry with heuristic
            try {
                Mime\Decode::splitMessage($raw, $headers, $content);
            } catch (Mail\Exception\RuntimeException $e) {
                static::fallbackMessageSplit($raw, $headers, $content);
            } catch (Mail\Exception\InvalidArgumentException $e) {
                static::fallbackMessageSplit($raw, $headers, $content);
            }
        }
    }

    public static function encodeHeaders(array &$headers): void
    {
        foreach ($headers as $k => $v) {
            // Laminas\Mail does not like empty headers, "Cc:" for example
            if ($v === '') {
                unset($headers[$k]);
            }

            // also it doesn't like 8bit headers
            if (Mime_Helper::is8bit($v)) {
                $headers[$k] = Mime_Helper::encode($v);
            }
        }
    }

    public static function convertHeaders(&$headers): void
    {
        // unfold message headers
        $headers = preg_replace("/\r?\n/", "\r\n", $headers);
        $headers = preg_replace("/\r\n(\t| )+/", ' ', $headers);

        // split by \r\n, but \r may be optional
        $headers = preg_split("/\r?\n/", $headers);

        // strip any leftover \r
        $headers = array_map('trim', $headers);

        static::encodeHeaders($headers);
        static::fixBrokenHeaders($headers);
    }

    private static function fallbackMessageSplit($raw, &$headers, &$content): void
    {
        // retry with manual \r\n splitting
        // retry our own splitting
        // message likely corrupted by Eventum itself
        $parts = explode("\r\n\r\n", $raw, 2);

        if (count($parts) === 1) {
            // not \r\n separated. retry with \n\n
            $parts = explode("\n\n", $raw, 2);
        }

        [$headers, $content] = $parts;

        self::convertHeaders($headers);
    }

    /**
     * Attempt to fix some known headers brokenness.
     *
     * @param string[] $headers
     */
    private static function fixBrokenHeaders(&$headers): void
    {
        $bag = new Headers();

        foreach ($headers as $index => &$line) {
            try {
                [$name, $value] = Header\GenericHeader::splitHeaderLine($line);
            } catch (Header\Exception\InvalidArgumentException $e) {
                if ($e->getMessage() === 'Invalid header value detected' && strpos($line, "\r") !== false) {
                    // it's very broken, at least attempt to strip \r
                    $line = str_replace("\r", '', $line);
                }

                if ($e->getMessage() === 'Invalid header name detected' && substr($line, 0, 5) === 'From ') {
                    // remove Mbox header from message if it is present
                    $line = "X-Broken-Header-Mbox: $line";
                }

                [$name, $value] = Header\GenericHeader::splitHeaderLine($line);
            }

            try {
                $bag->addHeaderLine($line);
            } catch (Mail\Exception\InvalidArgumentException $e) {
                // prefix invalid header with X-Broken-Header
                $line = "X-Broken-Header-$name: $value";
            }
        }
    }
}
