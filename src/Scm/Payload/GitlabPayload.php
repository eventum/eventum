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

namespace Eventum\Scm\Payload;

use Date_Helper;
use Eventum\Model\Entity\Commit;

class GitlabPayload implements PayloadInterface
{
    /** @var array */
    private $payload = [];

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function createCommit(array $commit): Commit
    {
        return (new Commit())
            ->setChangeset($commit['id'])
            ->setAuthorEmail($commit['author']['email'])
            ->setAuthorName($commit['author']['name'])
            ->setCommitDate(Date_Helper::getDateTime($commit['timestamp']))
            ->setMessage(trim($commit['message']));
    }

    /**
     * Get event name from payload.
     * The key is present for System Hooks
     */
    public function getEventName(): ?string
    {
        return $this->payload['event_name'] ?? null;
    }

    public function getEventType(): ?string
    {
        return $this->payload['event_type'] ?? null;
    }

    public function getAction(): ?string
    {
        return $this->payload['object_attributes']['action'] ?? null;
    }

    /**
     * Get description. Applies to issue events.
     */
    public function getDescription(): ?string
    {
        return $this->payload['object_attributes']['description'] ?? null;
    }

    /**
     * Get branch the commit was made on
     */
    public function getBranch(): ?string
    {
        $ref = $this->payload['ref'];

        if (strpos($ref, 'refs/heads/') === 0) {
            return substr($ref, 11);
        }

        return null;
    }

    public function getProject(): string
    {
        return $this->payload['project']['path_with_namespace'];
    }

    public function getCommits(): array
    {
        return $this->payload['commits'];
    }

    /**
     * Get repo url
     */
    public function getRepoUrl(): string
    {
        return explode(':', $this->payload['repository']['url'], 2)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
