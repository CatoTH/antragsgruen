<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\LoggedIn;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#typePolicyAmendments', LoggedIn::getPolicyID());
$I->submitForm('.adminTypeForm', [], 'save');

$I->logout();


$I->gotoMotion();
$I->seeElement('.sidebarActions .amendmentCreate');
$I->click('.sidebarActions .amendmentCreate a');
$I->see('Login', 'h1');
