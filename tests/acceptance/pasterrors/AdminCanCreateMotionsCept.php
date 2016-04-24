<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that admins can always create motions');

$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('input[name=managedUserAccounts]');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicyMotions', \app\models\policies\LoggedIn::getPolicyName());
$I->selectOption('#typePolicyAmendments', \app\models\policies\LoggedIn::getPolicyName());
$I->selectOption('#typePolicyComments', \app\models\policies\LoggedIn::getPolicyName());
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoMotionList();
$I->click('#newMotionBtn');
$I->seeElement('.createMotion1');

$I->gotoConsultationHome();
$I->dontSeeElement('.createMotion');
