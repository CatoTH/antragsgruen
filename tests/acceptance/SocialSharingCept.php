<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('see the sharing buttons');

$I->gotoMotion();
$I->seeElement('.shariff .twitter');
$I->seeElement('.shariff .facebook');

$I->gotoAmendment();
$I->seeElement('.shariff .twitter');
$I->seeElement('.shariff .facebook');


$I->wantTo('enforce login');

$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('input[name=forceLogin]');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');


$I->wantTo('verify that sharing buttons are deactivated');

$I->gotoMotion();
$I->dontSeeElement('.shariff .twitter');
$I->dontSeeElement('.shariff .facebook');

$I->gotoAmendment();
$I->dontSeeElement('.shariff .twitter');
$I->dontSeeElement('.shariff .facebook');
