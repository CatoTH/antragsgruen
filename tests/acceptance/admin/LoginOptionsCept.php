<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check the default options');
$I->gotoConsultationHome();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('#createAccount');
$I->seeElement('.loginWurzelwerk');
$I->dontSee('Admin-Login', '.loginWurzelwerk');
$I->seeElement('.loginOpenID');


$I->wantTo('deactivate Wurzelwerk and standard login');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->uncheckOption('.loginMethods .wurzelwerk input');
$I->submitForm('#siteSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('.loginWurzelwerk');
$I->see('Admin-Login', '.loginWurzelwerk');
$I->dontSeeElement('#admin_login_www');
$I->click('.loginWurzelwerk a');
$I->seeElement('#admin_login_www');
$I->seeElement('.loginOpenID');


// @TODO Now we've locked ourselves out; this should not happen
$scenario->incomplete('situation unresolvable yet');

$I->wantTo('deactivate everything (because I can)');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->uncheckOption('.loginMethods .external input');
$I->submitForm('#siteSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->dontSeeElement('.loginUsername');
$I->dontSeeElement('.loginOpenID');
$I->seeElement('.loginWurzelwerk');
$I->see('Admin-Login', '.loginWurzelwerk');
$I->dontSeeElement('#admin_login_www');
