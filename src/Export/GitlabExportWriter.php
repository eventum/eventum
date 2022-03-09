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

namespace Eventum\Export;

class GitlabExportWriter
{
    /**
     * https://docs.gitlab.com/ee/user/project/settings/import_export.html#version-history
     */
    private const EXPORT_VERSION = '0.2.4';

    /** @var string */
    private $exportVersion;
    /** @var string */
    private $directory;

    public function __construct(string $directory, string $exportVersion = self::EXPORT_VERSION)
    {
        $this->directory = $directory;
        $this->exportVersion = $exportVersion;
    }

    public function export(): void
    {
        $this->writeVersion();
        $this->writeProject();
    }

    private function writeVersion(): void
    {
        $this->writeFile('VERSION', $this->exportVersion);
    }

    private function writeProject(): void
    {
        $this->writeJsonFile('tree/project.json', [
            'description' => '',
            'visibility_level' => 10,
            'archived' => false,
            'shared_runners_enabled' => true,
            'build_coverage_regex' => null,
            'build_allow_git_fetch' => true,
            'build_timeout' => 3600,
            'pending_delete' => false,
            'public_builds' => true,
            'last_repository_check_failed' => null,
            'only_allow_merge_if_pipeline_succeeds' => false,
            'has_external_issue_tracker' => false,
            'request_access_enabled' => false,
            'has_external_wiki' => false,
            'only_allow_merge_if_all_discussions_are_resolved' => false,
            'printing_merge_request_link_enabled' => true,
            'auto_cancel_pending_pipelines' => 'enabled',
            'ci_config_path' => null,
            'delete_error' => null,
            'merge_requests_rebase_enabled' => false,
            'merge_requests_ff_only_enabled' => false,
            'resolve_outdated_diff_discussions' => false,
            'jobs_cache_index' => 1,
            'pages_https_only' => false,
            'external_authorization_classification_label' => null,
            'disable_overriding_approvers_per_merge_request' => false,
            'issues_template' => null,
            'merge_requests_author_approval' => true,
            'merge_requests_disable_committers_approval' => false,
            'merge_requests_template' => '',
            'require_password_to_approve' => false,
            'reset_approvals_on_push' => true,
            'service_desk_enabled' => true,
            'approvals_before_merge' => 0,
            'remove_source_branch_after_merge' => true,
            'suggestion_commit_message' => '',
            'autoclose_referenced_issues' => false,
        ]);
    }

    private function writeFile(string $fileName, string $content): void
    {
        FileUtil::writeFile($this->directory . '/' . $fileName, $content);
    }

    private function writeJsonFile(string $fileName, array $data): void
    {
        $this->writeFile($fileName, json_encode($data, JSON_THROW_ON_ERROR));
    }
}
