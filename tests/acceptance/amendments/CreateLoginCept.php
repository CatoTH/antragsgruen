<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicyAmendments', \app\models\policies\LoggedIn::getPolicyName());
$I->submitForm('.adminTypeForm', [], 'save');

$I->logout();


$I->gotoMotion();
$I->seeElement('.sidebarActions .amendmentCreate');
$I->click('.sidebarActions .amendmentCreate a');
$I->see('Login', 'h1');
