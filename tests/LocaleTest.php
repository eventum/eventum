<?php

class LocaleTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        self::skipJenkins('Tests require full localedb installation');

        $localeStamp = __DIR__ . '/_locales.stamp';
        if (!file_exists($localeStamp)) {
            self::installLocales();
            touch($localeStamp);
        }
    }

    private static function installLocales()
    {
        $localeDir = APP_PATH . '/localization';

        $rc = system("make -sC $localeDir install localedir=.");
        if ($rc != 0) {
            throw new RuntimeException("Locale setup failed");
        }
    }

    /**
     * @test
     * @dataProvider availableLanguages
     */
    public function testLocales($code, $language)
    {
        $enabled = Language::set($code);
        $this->assertTrue($enabled, "Language '$language' ($code) is valid");
    }

    public function availableLanguages()
    {
        $langs = Language::getAvailableLanguages(false);

        $res = array();
        foreach ($langs as $code => $language) {
            $res[] = array($code, $language);
        }
        return $res;
    }
}
