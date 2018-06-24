<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check the default options');
$I->gotoConsultationHome();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('#createAccount');
$I->seeElement('.loginOpenID');


$I->wantTo('deactivate external login');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->uncheckOption('.loginMethods .external input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->dontSeeCheckboxIsChecked('.loginMethods .external input');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->dontSeeElement('.loginOpenID');



$I->wantTo('activate external login');

$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();

$I->dontSeeCheckboxIsChecked('.loginMethods .external input');
$I->checkOption('.loginMethods .external input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->seeCheckboxIsChecked('.loginMethods .external input');

$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->seeElement('.loginUsername');
$I->seeElement('.loginOpenID');
