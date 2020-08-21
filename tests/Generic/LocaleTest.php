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

namespace Eventum\Test\Generic;

use Eventum\Config\Paths;
use Eventum\Test\TestCase;
use Language;
use RuntimeException;

/**
 * @group locale
 */
class LocaleTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!getenv('TRAVIS')) {
            self::markTestSkipped('Tests require full localedb installation');
        }

        $localeStamp = __DIR__ . '/_locales.stamp';
        if (!file_exists($localeStamp)) {
            self::installLocales();
            touch($localeStamp);
        }
    }

    private static function installLocales(): void
    {
        $localeDir = Paths::APP_PATH . '/localization';

        $rc = system("make -sC $localeDir install localedir=.");
        if ($rc !== 0) {
            throw new RuntimeException('Locale setup failed');
        }
    }

    /**
     * @dataProvider availableLanguages
     * @group locale
     */
    public function testLocales(string $code, string $language): void
    {
        $enabled = Language::set($code);
        $this->assertTrue($enabled, "Language '$language' ($code) is valid");
    }

    public function availableLanguages(): iterable
    {
        $langs = Language::getAvailableLanguages(false);

        foreach ($langs as $code => $language) {
            yield [$code, $language];
        }
    }
}
