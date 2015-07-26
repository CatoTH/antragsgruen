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


$I->wantTo('deactivate Wurzelwerk and external login');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->uncheckOption('.loginMethods .wurzelwerk input');
$I->uncheckOption('.loginMethods .external input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->dontSeeCheckboxIsChecked('.loginMethods .wurzelwerk input');
$I->dontSeeCheckboxIsChecked('.loginMethods .external input');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('.loginWurzelwerk');
$I->see('Admin-Login', '.loginWurzelwerk');
$I->dontSeeElement('#admin_login_www');
$I->click('.loginWurzelwerk a');
$I->seeElement('#admin_login_www');
$I->dontSeeElement('.loginOpenID');



$I->wantTo('activate Wurzelwerk and external login');

$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();

$I->dontSeeCheckboxIsChecked('.loginMethods .wurzelwerk input');
$I->dontSeeCheckboxIsChecked('.loginMethods .external input');
$I->checkOption('.loginMethods .wurzelwerk input');
$I->checkOption('.loginMethods .external input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->seeCheckboxIsChecked('.loginMethods .wurzelwerk input');
$I->seeCheckboxIsChecked('.loginMethods .external input');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('.loginWurzelwerk');
$I->dontSee('Admin-Login', '.loginWurzelwerk');
$I->seeElement('.loginOpenID');
