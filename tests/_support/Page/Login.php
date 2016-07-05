<?php
namespace Page;

class Login
{
    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    // include url of current page
    public static $URL = '';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL . $param;
    }

    public function login($name, $password)
    {
        $I = $this->tester;

        $I->amOnPage('/');

        $I->see('Login:');
        $I->see('Password:');

        $I->fillField('email', $name);
        $I->fillField('passwd', $password);
        $I->click('Login');

        return $this;
    }
}
