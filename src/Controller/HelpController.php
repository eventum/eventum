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

namespace Eventum\Controller;

use Auth;
use Eventum\Config\Paths;
use Eventum\Markdown\MarkdownRendererInterface;
use Eventum\ServiceContainer;
use Help;

class HelpController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'help/index.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * @return string
     */
    private function getTopic(): string
    {
        $get = $this->getRequest()->query;
        $topic = $get->get('topic');

        if (!$topic || !Help::topicExists($topic)) {
            $topic = 'main';
        }

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $topic = $this->getRequest()->query->get('topic', 'index');
        $topicPath = $this->getTopicPath($topic);
        if (!file_exists($topicPath)) {
            $topicPath = $this->getTopicPath('index');
        }

        $markdown = file_get_contents($topicPath);
        $help = $this->renderTemplate($markdown);

        $topic = 'main'; // backward compat
        $this->tpl->assign(
            [
                'help' => $help,
                'topic' => $topic,
                'links' => Help::getNavigationLinks($topic),
            ]
        );

        if ($topic !== 'main') {
            $this->tpl->assign('child_links', Help::getChildLinks($topic));
        }
    }

    private function renderTemplate(string $markdown): string
    {
        $renderer = ServiceContainer::get(MarkdownRendererInterface::RENDER_BLOCK);

        return $renderer->render($markdown);
    }

    private function getTopicPath(string $topic): string
    {
        if (!$topic || $topic === 'main') {
            $topic = 'index';
        } else {
            // avoid path traversal
            $topic = basename($topic);
        }

        return sprintf('%s/%s.md', Paths::APP_HELP_PATH, $topic);
    }
}
