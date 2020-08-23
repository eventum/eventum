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

namespace Eventum\Scm;

use Eventum\Config\Config;
use Eventum\Logger\LoggerTrait;
use Eventum\Model\Entity\Commit;
use Eventum\Model\Entity\CommitFile;
use Eventum\ServiceContainer;

final class ScmRepository
{
    use LoggerTrait;

    /** @var array|Config */
    private $config = [];

    public function __construct(string $name)
    {
        $setup = ServiceContainer::getConfig();

        if (isset($setup['scm'][$name])) {
            $this->config = $setup['scm'][$name];
        } else {
            $this->warning("SCM Repo '$name' not defined");
        }
    }

    public function getName(): string
    {
        return $this->config['name'];
    }

    public static function getAllRepos(): Config
    {
        return ServiceContainer::getConfig()['scm'];
    }

    /**
     * Get CommitRepo from $repo_url
     * Walk over all configured scm to find one by matching url.
     *
     * @param string $repo_url
     * @return ScmRepository
     */
    public static function getRepoByUrl(string $repo_url): ?self
    {
        foreach (static::getAllRepos() as $name => $scm) {
            if (!$scm->urls) {
                continue;
            }

            foreach ($scm->urls as $url) {
                if ($url === $repo_url) {
                    return new static($scm->name);
                }
            }
        }

        return null;
    }

    /**
     * The scm may be configured to accept only certain branches or exclude some
     *
     * 'only' - Defines a list of git refs which to accept
     * 'except' - Defines a list of git refs which not to accept
     *
     * These accept exact branch names.
     * This could be changed to glob in the future,
     * currently i'm only interested of 'master' branch commits
     *
     * @param string $branch
     * @return bool
     */
    public function branchAllowed(?string $branch): bool
    {
        if (!$branch) {
            // no branch given, the SCM doesn't support branching?
            return true;
        }

        // 'only' present, check it
        if (isset($this->config['only'])) {
            return in_array($branch, $this->config['only']->toArray(), true);
        }

        // if 'except' present
        if (isset($this->config['except'])) {
            return !in_array($branch, $this->config['except']->toArray(), true);
        }

        return true;
    }

    public function getCheckoutUrl(Commit $commit, CommitFile $cf): string
    {
        return $this->getUrl('checkout_url', $commit, $cf);
    }

    public function getDiffUrl(Commit $commit, CommitFile $cf): string
    {
        return $this->getUrl('diff_url', $commit, $cf);
    }

    public function getLogUrl(Commit $commit, CommitFile $cf): string
    {
        return $this->getUrl('log_url', $commit, $cf);
    }

    /**
     * Get link to commit (not file specific)
     */
    public function getChangesetUrl(Commit $commit): string
    {
        return $this->getUrl('changeset_url', $commit);
    }

    /**
     * Get link to project the commit was made in.
     */
    public function getProjectUrl(Commit $commit): string
    {
        return $this->getUrl('project_url', $commit);
    }

    /**
     * Get link to branch the commit was made in.
     */
    public function getBranchUrl(Commit $commit): string
    {
        return $this->getUrl('branch_url', $commit);
    }

    /**
     * Method used to parse an user provided URL and substitute a known set of
     * placeholders for the appropriate information.
     *
     * @param string $key the url key to lookup from config
     * @param Commit $commit
     * @param CommitFile $cf
     * @return string The parsed URL
     */
    private function getUrl(string $key, Commit $commit, CommitFile $cf = null): string
    {
        // $url will be null if key doesn't exist, so no need to check it
        $url = $this->config[$key];

        $replace = [
            '{PROJECT}' => $commit->getProjectName(),
            '{CHANGESET}' => $commit->getChangeset(),
            '{BRANCH}' => $commit->getBranch(),
        ];

        if ($cf) {
            $replace['{FILE}'] = $cf->getFilename();
            $replace['{OLD_VERSION}'] = $cf->getOldVersion();
            $replace['{NEW_VERSION}'] = $cf->getNewVersion();
        }

        return str_replace(array_keys($replace), array_values($replace), $url);
    }
}
