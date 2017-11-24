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
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @param array $commit
     * @return Commit
     */
    public function createCommit($commit)
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
     *
     * @return string
     */
    public function getEventName()
    {
        if (!isset($this->payload['event_name'])) {
            return null;
        }

        return $this->payload['event_name'];
    }

    /**
     * Get branch the commit was made on
     *
     * @return string
     */
    public function getBranch()
    {
        $ref = $this->payload['ref'];

        if (strpos($ref, 'refs/heads/') === 0) {
            return substr($ref, 11);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->payload['project']['path_with_namespace'];
    }

    /**
     * @return array
     */
    public function getCommits()
    {
        return $this->payload['commits'];
    }

    /**
     * Get repo url
     *
     * @return string
     */
    public function getRepoUrl()
    {
        return explode(':', $this->payload['repository']['url'], 2)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
