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

namespace Eventum\ServiceProvider;

use Eventum\Config\Paths;
use Eventum\Markdown;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;
use Misc;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    private const PURIFIER_CACHE_DIR = Paths::APP_CACHE_PATH . '/purifier';

    public function register(Container $app): void
    {
        $app[Markdown\MarkdownRendererInterface::class] = static function ($app) {
            return new Markdown\MarkdownRenderer($app[HTMLPurifier::class]);
        };

        $app[HTMLPurifier::class] = static function () {
            return self::createPurifier();
        };
    }

    private static function createPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_HTML5Config::createDefault();

        $config->set('AutoFormat.AutoParagraph', true);
        // remove empty tag pairs
        $config->set('AutoFormat.RemoveEmpty', true);
        // remove empty, even if it contains an &nbsp;
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        // preserve html comments
        $config->set('HTML.AllowedCommentsRegexp', '/.+/');

        // disable tidy processing, even if extension present
        $config->set('Output.TidyFormat', false);

        // disable useless normalizer we do not need
        $config->set('Core.NormalizeNewlines', false);

        // allow tasklist <input> checkboxes
        // https://github.com/ezyang/htmlpurifier/issues/213#issuecomment-487206892
        $config->set('HTML.Trusted', true);
        $config->set('HTML.ForbiddenElements', ['script', 'noscript']);

        // Absolute path with no trailing slash to store serialized definitions in.
        $config->set('Cache.SerializerPath', Misc::ensureDir(self::PURIFIER_CACHE_DIR));

        return new HTMLPurifier($config);
    }
}
