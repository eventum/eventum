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

namespace Eventum\Model\Entity;

class GitlabScmPayload
{
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get branch the commit was made on
     *
     * @return string
     */
    public function getBranch()
    {
        $ref = $this->payload['ref'];

        if (substr($ref, 0, 11) == 'refs/heads/') {
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
        return current(explode(':', $this->payload['repository']['url'], 2));
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
