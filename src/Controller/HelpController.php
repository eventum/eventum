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
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Inline\Element\Link;

class HelpController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'help.tpl.html';

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

        $this->tpl->assign(
            [
                'help' => $help,
            ]
        );
    }

    private function renderTemplate(string $markdown): string
    {
        /** @var MarkdownRendererInterface $renderer */
        $renderer = ServiceContainer::get(MarkdownRendererInterface::RENDER_BLOCK);

        $environment = $renderer->getEnvironment();

        // enable soft breaks in this renderer
        $environment->mergeConfig([
                'renderer' => [
                    'soft_break' => "\n",
                ],
            ]
        );

        // convert markdown links to help
        $environment->addEventListener(DocumentParsedEvent::class, static function (DocumentParsedEvent $e) {
            $walker = $e->getDocument()->walker();

            while ($event = $walker->next()) {
                $node = $event->getNode();
                if (!$node instanceof Link || !$event->isEntering()) {
                    continue;
                }

                // match relative link with .md extension
                if (!preg_match('#^([^/]+)\.md$#', $node->getUrl(), $m)) {
                    continue;
                }
                $topic = $m[1];
                $node->setUrl('help.php?topic=' . $topic);
            }
        });

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
