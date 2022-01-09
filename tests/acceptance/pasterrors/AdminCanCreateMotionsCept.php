<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that admins can always create motions');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.managedUserAccounts input');
$page->saveForm();

$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicyMotions', \app\models\policies\LoggedIn::getPolicyID());
$I->selectOption('#typePolicyAmendments', \app\models\policies\LoggedIn::getPolicyID());
$I->selectOption('#typePolicyAmendments', \app\models\policies\LoggedIn::getPolicyID());
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoMotionList();
$I->click('#newMotionBtn');
$I->seeElement('.createMotion1');

$I->gotoConsultationHome();
$I->dontSeeElement('.createMotion');
