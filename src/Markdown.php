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

namespace Eventum;

use Eventum\CommonMark\MentionExtension;
use Eventum\EventDispatcher\EventManager;
use HTMLPurifier;
use HTMLPurifier_Config;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment;
use League\CommonMark\Ext\Autolink\AutolinkExtension;
use League\CommonMark\Ext\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use Lossendae\CommonMark\TaskLists\TaskListsCheckbox;
use Lossendae\CommonMark\TaskLists\TaskListsCheckboxRenderer;
use Lossendae\CommonMark\TaskLists\TaskListsParser;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webuni\CommonMark\TableExtension\TableExtension;

class Markdown
{
    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    /** @var HTMLPurifier */
    private $purifier;
    /** @var ConverterInterface[] */
    private $converter = [];

    public function __construct()
    {
        $this->purifier = $this->createPurifier();
    }

    public function render(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(false)->convertToHtml($text);

        return $this->getPurifier()->purify($html);
    }

    public function renderInline(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(true)->convertToHtml($text);

        return $this->getPurifier()->purify($html);
    }

    private function getConverter(bool $inline): ConverterInterface
    {
        return $this->converter[(int)$inline] ?? $this->converter[(int)$inline] = $this->createConverter($inline);
    }

    private function getPurifier(): HTMLPurifier
    {
        return $this->purifier ?? $this->purifier = $this->createPurifier();
    }

    private function createConverter(bool $inline): ConverterInterface
    {
        $config = [
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => "\n",
                'soft_break' => "<br />\n",
            ],
            'html_input' => Environment::HTML_INPUT_ALLOW,
            'allow_unsafe_links' => false,
            'max_nesting_level' => self::MAX_NESTING_LEVEL,
        ];

        $environment = new Environment($config);
        if ($inline) {
            $environment->addExtension(new InlinesOnlyExtension());
        } else {
            $environment->addExtension(new CommonMarkCoreExtension());
        }

        $this->applyExtensions($environment);

        return new CommonMarkConverter([], $environment);
    }

    private function createPurifier(): HTMLPurifier
    {
        $cacheDir = APP_VAR_PATH . '/cache/purifier';
        is_dir($cacheDir) || mkdir($cacheDir, 02775) || is_dir($cacheDir);

        /// https://gist.github.com/ctrl-freak/1188139
        $config = HTMLPurifier_Config::createDefault();

        $config->set('AutoFormat.AutoParagraph', true);
        // remove empty tag pairs
        $config->set('AutoFormat.RemoveEmpty', true);
        // remove empty, even if it contains an &nbsp;
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);

        // Absolute path with no trailing slash to store serialized definitions in.
        $config->set('Cache.SerializerPath', $cacheDir);
        $def = $config->getHTMLDefinition(true);
        $def->addBlankElement('details');
        $def->addElement('details', 'Block', 'Flow', 'Common');
        $def->addElement('summary', 'Block', 'Flow', 'Common');

        return new HTMLPurifier($config);
    }

    private function applyExtensions(Environment $environment): void
    {
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new MentionExtension());

        $environment->addInlineRenderer(TaskListsCheckbox::class, new TaskListsCheckboxRenderer());
        $environment->addInlineParser(new TaskListsParser());

        // allow extensions to apply behaviour
        $event = new GenericEvent($environment);
        EventManager::dispatch(Event\SystemEvents::MARKDOWN_ENVIRONMENT_CONFIGURE, $event);
    }
}
