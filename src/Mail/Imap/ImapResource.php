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

namespace Eventum\Mail\Imap;

use LazyProperty\LazyPropertiesTrait;

class ImapResource
{
    use LazyPropertiesTrait;

    /** @var int */
    public $num;
    /** @var object */
    public $imapheaders;
    /** @var object */
    public $overview;
    /** @var string */
    public $headers;
    /** @var string */
    public $content;
    /** @var resource */
    private $mbox;

    public function __construct($resource, int $num)
    {
        $this->num = $num;
        $this->mbox = $resource;
        $this->initLazyProperties(['imapheaders', 'overview', 'headers', 'content']);
    }

    public function __toString()
    {
        $messageId = isset($this->imapheaders) ? $this->imapheaders->message_id : 'uninitialized';

        return sprintf('#%d %s', $this->num, $messageId);
    }

    /**
     * Return true if message is \Seen, \Deleted or \Answered
     *
     * @return bool
     */
    public function isSeen(): bool
    {
        return (
            $this->overview->seen
            || $this->overview->deleted
            || $this->overview->answered
        );
    }

    /**
     * @return object
     */
    private function getImapHeaders()
    {
        return imap_headerinfo($this->mbox, $this->num);
    }

    /**
     * @return object
     */
    private function getOverview()
    {
        [$overview] = imap_fetch_overview($this->mbox, $this->num);

        return $overview;
    }

    /**
     * @return string
     */
    private function getHeaders(): string
    {
        return imap_fetchheader($this->mbox, $this->num);
    }

    /**
     * @return string
     */
    private function getContent(): string
    {
        return imap_body($this->mbox, $this->num);
    }
}
