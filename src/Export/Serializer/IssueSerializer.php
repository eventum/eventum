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
use Eventum\Export\ValueConverter\DateTimeValueConverter;
use Eventum\Logger\LoggerTrait;
use Eventum\Model\Entity\Issue;
use Psr\Log\LoggerInterface;

class IssueSerializer
{
    use LoggerTrait;

    // "2021-05-19T14:16:35.842+03:00"
    private const DATE_FORMAT = DATE_RFC3339;

    /** @var DateTimeValueConverter */
    private $dateTimeConverter;
    /** @var array */
    private $template;

    public function __construct(LoggerInterface $logger)
    {
        $this->dateTimeConverter = new DateTimeValueConverter();
        $fileName = Paths::APP_RESOURCES_PATH . '/export/gitlab/issue.json';
        $this->template = FileUtil::readJsonFile($fileName);
        $this->logger = $logger;
    }

    public function __invoke(Issue $issue): array
    {
        $data = $this->template;

        $data['title'] = $issue->getSummary();
        $data['author_id'] = $this->getAuthorId($issue);
        $data['created_at'] = $this->dateTimeConverter->convert($issue->getCreatedDate());
        $data['updated_at'] = $this->dateTimeConverter->convert($issue->getUpdatedDate());
        $data['description'] = $this->getDescription($issue);
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

    private function getDescription(Issue $issue, int $maxLength = 10000): string
    {
        $description = $issue->getDescription();
        if (strlen($description) >= $maxLength) {
            $this->warning("Description for #{$issue->getId()} length over {$maxLength}, truncating");
            $description = mb_substr($description, 0, $maxLength, 'UTF-8');
        }

        return $description;
    }
}
