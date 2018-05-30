<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectFueluxOption('#typePolicyAmendments', \app\models\policies\LoggedIn::getPolicyID());
$I->submitForm('.adminTypeForm', [], 'save');

$I->logout();


$I->gotoMotion();
$I->seeElement('.sidebarActions .amendmentCreate');
$I->click('.sidebarActions .amendmentCreate a');
$I->see('Login', 'h1');
