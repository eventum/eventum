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

namespace Eventum\Export\Serializer;

use Eventum\Config\Paths;
use Eventum\Export\FileUtil;
use Eventum\Model\Entity\Issue;
use Port\ValueConverter\DateTimeToStringValueConverter;

class IssueSerializer
{
    // "2021-05-19T14:16:35.842+03:00"
    private const DATE_FORMAT = DATE_RFC3339;

    /** @var DateTimeToStringValueConverter */
    private $dateTimeConverter;
    /** @var array */
    private $template;

    public function __construct()
    {
        $this->dateTimeConverter = new DateTimeToStringValueConverter(self::DATE_FORMAT);
        $fileName = Paths::APP_RESOURCES_PATH . '/export/gitlab/issue.json';
        $this->template = FileUtil::readJsonFile($fileName);
    }

    public function __invoke(Issue $issue): array
    {
        $dateTimeConverter = $this->dateTimeConverter;
        $data = $this->template;

        $data['title'] = $issue->getSummary();
        $data['author_id'] = $this->getAuthorId($issue);
        $data['created_at'] = $dateTimeConverter($issue->getCreatedDate());
        $data['updated_at'] = $dateTimeConverter($issue->getUpdatedDate());
        $data['description'] = $issue->getDescription();
        $data['iid'] = $issue->getId();
        $data['state'] = $this->getState($issue);

        return $data;
    }

    private function getAuthorId(Issue $issue): int
    {
        return $issue->getUserId();
    }

    private function getState(Issue $issue): string
    {
        return $issue->getStatus()->isClosed() ? 'closed' : 'opened';
    }
}
