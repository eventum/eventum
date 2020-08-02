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

namespace Eventum\Scm\Adapter;

use Eventum\Db\Doctrine;
use Eventum\Scm\Payload\StandardPayload;
use Eventum\ServiceContainer;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Standard SCM handler
 */
class Standard extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function can(): bool
    {
        // must be POST
        if ($this->request->getMethod() !== Request::METHOD_POST) {
            return false;
        }

        // require 'scm' GET parameter to be 'svn' or 'git'
        return in_array($this->request->query->get('scm'), ['svn', 'git'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $payload = $this->getPayload();

        $issues = $payload->getIssues();
        if (!$issues) {
            throw new InvalidArgumentException('No issues provided');
        }

        $ci = $payload->createCommit();
        $repo = $ci->getCommitRepo();

        if (!$repo->branchAllowed($payload->getBranch())) {
            throw new InvalidArgumentException("Branch not allowed: {$payload->getBranch()}");
        }

        $ci->setChangeset($payload->getCommitId());

        $em = ServiceContainer::getEntityManager();
        $cr = Doctrine::getCommitRepository();

        $cr->preCommit($ci, $payload);

        // add commit files
        $cr->addCommitFiles($ci, $payload->getFiles());
        // add commits to issues
        $cr->addIssues($ci, $issues);

        $em->persist($ci);
        $em->flush();
    }

    /*
     * Get Hook Payload
     */
    private function getPayload(): StandardPayload
    {
        $data = json_decode($this->request->getContent(), true);

        return new StandardPayload($data);
    }
}
