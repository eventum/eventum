<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

namespace Eventum\Mail\Helper;

use Zend\Mail\Headers;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\MessageId;
use MailMessage;
use Mail_Helper;
use DomainException;

class SanitizeHeaders
{
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
            $messageId = Mail_Helper::generateMessageID($text_headers, $mail->getContent());
            $header = new MessageId();
            $headers->addHeader($header->setId(trim($messageId, '<>')));
        }

        // headers to check and whether they need to be unique
        $checkHeaders = array(
            'From' => true,
            'Subject' => true,
            'Message-Id' => true,
            'To' => false,
            'Cc' => false,
        );
        // NOTE: the headerClass does not match the format for Message-Id,
        // but luckily Message-Id header is always present (see above)
        foreach ($checkHeaders as $headerName => $unique) {
            $header = $mail->getHeaderByName($headerName, $headerName);
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
