<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('see the sharing buttons');

$I->gotoMotion();
$I->seeElement('.share_buttons .twitter');
$I->seeElement('.share_buttons .facebook');

$I->gotoAmendment();
$I->seeElement('.share_buttons .twitter');
$I->seeElement('.share_buttons .facebook');


$I->wantTo('enforce login');

$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('input[name=forceLogin]');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');


$I->wantTo('verify that sharing buttons are deactivated');

$I->gotoMotion();
$I->dontSeeElement('.share_buttons .twitter');
$I->dontSeeElement('.share_buttons .facebook');

$I->gotoAmendment();
$I->dontSeeElement('.share_buttons .twitter');
$I->dontSeeElement('.share_buttons .facebook');
