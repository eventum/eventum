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

use DomainException;
use Eventum\Mail\MailMessage;
use Mail_Helper;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\MessageId;
use Zend\Mail\Headers;

class SanitizeHeaders
{
    /**
     * Namespace for Header classes
     */
    const HEADER_NS = '\\Zend\\Mail\\Header\\';

    /**
     * Sanitize Mail headers:
     *
     * - generate MessageId header in case it is missing
     * - make common fields unique (From, Subject, Message-Id)
     * - or merge them (To, Cc)
     *
     * @param MailMessage $mail
     */
    public function __invoke(MailMessage $mail)
    {
        $headers = $mail->getHeaders();

        // add Message-Id, this needs to be first before we modify more headers
        if (!$headers->has('Message-Id')) {
            // add Message-Id header as it is missing
            $text_headers = rtrim($headers->toString(), Headers::EOL);
            $body = $mail->hasContent() ? $mail->getContent() : null;
            $messageId = Mail_Helper::generateMessageID($text_headers, $body);
            $header = new MessageId();
            $headers->addHeader($header->setId(trim($messageId, '<>')));
        }

        // headers to check and whether they need to be unique
        $checkHeaders = [
            'From' => true,
            'Subject' => true,
            'Message-Id' => true,
            'To' => false,
            'Cc' => false,
        ];
        // NOTE: the headerClass does not match the format for Message-Id,
        // but luckily Message-Id header is always present (see above)
        foreach ($checkHeaders as $headerName => $unique) {
            $headerClass = self::HEADER_NS . $headerName;
            $header = $mail->getHeaderByName($headerName, $headerClass);
            if ($unique) {
                $this->removeDuplicateHeader($headers, $header);
            } else {
                $this->mergeDuplicateHeader($headers, $header);
            }
        }
    }

    /**
     * Helper to remove duplicate headers, but keep only one.
     *
     * Note: headers order is changed when duplicate header is removed (header is removed and appended to the headers array)
     *
     * @param Headers $headerBag
     * @param HeaderInterface|HeaderInterface[] $headers
     */
    private function removeDuplicateHeader(Headers $headerBag, $headers)
    {
        if ($headers instanceof HeaderInterface) {
            // all good
            return;
        }

        $headerBag->removeHeader($headers[0]->getFieldName());
        $headerBag->addHeader($headers[0]);
    }

    /**
     * Merge duplicate header fields into single headers field.
     * The headers must be AbstractAddressList.
     *
     * @param Headers $headerBag
     * @param HeaderInterface|AbstractAddressList[] $headers
     */
    private function mergeDuplicateHeader(Headers $headerBag, $headers)
    {
        if ($headers instanceof HeaderInterface) {
            // all good
            return;
        }

        // use first headers as base and collect addresses there
        $header = $headers[0];
        unset($headers[0]);

        if (!$header instanceof AbstractAddressList) {
            throw new DomainException(
                sprintf(
                    'Cannot grab address list from headers of type "%s"; not an AbstractAddressList implementation',
                    get_class($header)
                )
            );
        }

        $addressList = $header->getAddressList();
        foreach ($headers as $h) {
            $addressList->merge($h->getAddressList());
        }
        $headerBag->removeHeader($header->getFieldName());
        $headerBag->addHeader($header);
    }
}
