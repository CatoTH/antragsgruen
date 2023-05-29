<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome(false);
$I->see('Test2', 'h1');

$I->wantTo('enforce login');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.forceLogin input');
$page->saveForm();
$I->logout();

$I->gotoConsultationHome(false);
$I->see('Login', 'h1');

$I->wantTo('log in');
$I->loginAsStdUser();
$I->see('Test2', 'h1');


$I->wantTo('disable it again');
$I->logout();
$I->gotoConsultationHome(false);
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoConsultation();
$I->uncheckOption('.forceLogin input');
$page->saveForm();
$I->logout();

$I->gotoConsultationHome(false);
$I->see('Test2', 'h1');
