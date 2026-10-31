<?php

/** @var \Codeception\Scenario $scenario */
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$grabCookies = function () use ($I): array {
    return $I->executeInSelenium(fn(RemoteWebDriver $wd) => $wd->manage()->getCookies());
};
$restoreCookies = function (array $cookies, array $onlyNames = []) use ($I): void {
    $I->executeInSelenium(function (RemoteWebDriver $wd) use ($cookies, $onlyNames) {
        $wd->manage()->deleteAllCookies();
        foreach ($cookies as $cookie) {
            if (count($onlyNames) === 0 || in_array($cookie->getName(), $onlyNames, true)) {
                $wd->manage()->addCookie($cookie);
            }
        }
    });
};

$I->wantTo('log in twice, creating two independent sessions');

$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('LOGOUT', '#logoutLink');
$cookiesA = $grabCookies();

$restoreCookies([]);
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('LOGOUT', '#logoutLink');
$cookiesB = $grabCookies();


$I->wantTo('check that the first session is still valid (session and remember-me cookie on their own)');

$restoreCookies($cookiesA, ['PHPSESSID']);
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');

$restoreCookies($cookiesA, ['_identity']);
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');


$I->wantTo('change the password in the second session');

$restoreCookies($cookiesB);
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');
$I->click('#myAccountLink');
$I->fillField('#userPwd', '12345678');
$I->fillField('#userPwd2', '12345678');
$I->submitForm('.userAccountForm', [], 'save');
$I->see('Gespeichert.');
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');
$cookiesBAfter = $grabCookies();


$I->wantTo('check that the first session is logged out');

$restoreCookies($cookiesA, ['PHPSESSID']);
$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');

$restoreCookies($cookiesA, ['_identity']);
$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');

$restoreCookies($cookiesA);
$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');


$I->wantTo('check that the old cookies of the second session are invalid, but the renewed ones are valid');

$restoreCookies($cookiesB, ['_identity']);
$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');

$restoreCookies($cookiesBAfter, ['PHPSESSID']);
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');

$restoreCookies($cookiesBAfter, ['_identity']);
$I->gotoConsultationHome();
$I->see('LOGOUT', '#logoutLink');
