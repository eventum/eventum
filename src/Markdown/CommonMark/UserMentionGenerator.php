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

namespace Eventum\Markdown\CommonMark;

use League\CommonMark\Extension\Mention\Generator\MentionGeneratorInterface;
use League\CommonMark\Extension\Mention\Mention;
use League\CommonMark\Inline\Element\AbstractInline;

class UserMentionGenerator implements MentionGeneratorInterface
{
    /** @var string */
    private $baseUrl;
    /** @var UserLookup */
    private $lookup;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->lookup = new UserLookup();
    }

    public function generateMention(Mention $mention): ?AbstractInline
    {
        $user = $this->lookup->findUser($mention->getIdentifier());

        if (!$user) {
            return null;
        }

        $link = $this->baseUrl . sprintf('/list.php?reporter=%s&hide_closed=1', $user->getId());
        $mention->setUrl($link);

        $title = ev_gettext('View %s issues', $user->getFullName());
        $mention->data['title'] = $title;

        return $mention;
    }
}
