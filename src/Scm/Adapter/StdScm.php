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

use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;
use InvalidArgumentException;
use Issue;

/**
 * Standard SCM handler
 *
 * @package Eventum\Scm
 */
class StdScm extends AbstractScmAdapter
{
    /**
     * @inheritdoc
     */
    public function can()
    {
        // require 'scm' GET parameter to be 'svn' or 'git'
        return in_array($this->request->query->get('scm'), array('svn', 'git'));
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        $params = $this->request->query;

        $issues = $this->getIssues($params);
        if (!$issues) {
            throw new InvalidArgumentException('No issues provided');
        }

        $ci = $this->createCommit($params);
        $repo = new Entity\CommitRepo($ci->getScmName());
        if (!$repo->branchAllowed($ci->getBranch())) {
            throw new \InvalidArgumentException("Branch not allowed: {$ci->getBranch()}");
        }

        $ci->setChangeset($params->get('commitid'));

        $cr = CommitRepository::create();
        $cr->preCommit($ci, $params);
        $ci->save();

        // save issue association
        foreach ($issues as $issue_id) {
            Entity\IssueCommit::create()
                ->setCommitId($ci->getId())
                ->setIssueId($issue_id)
                ->save();

            // print report to stdout of commits so hook could report status back to commiter
            $details = Issue::getDetails($issue_id);
            echo "#$issue_id - {$details['iss_summary']} ({$details['sta_title']})\n";
        }

        // save commit files
        $files = $this->getFiles($params);
        foreach ($files as $file) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file['file'])
                ->setOldVersion($file['old_version'])
                ->setNewVersion($file['new_version']);
            $cf->save();
            $ci->addFile($cf);
        }

        foreach ($issues as $issue_id) {
            $cr->addCommit($issue_id, $ci);
        }
    }
}
