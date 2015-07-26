<?php

/** @var \Codeception\Scenario $scenario */
use app\tests\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the home page');
$consultationHome = $I->gotoConsultationHome();
$I->validatePa11y();
$I->validateHTML();

$I->wantTo('test the motion creation form');
$consultationHome->gotoMotionCreatePage();
// No validateHTML, as CKEditor modifies the HTML code in a way that makes it invalid
$I->validatePa11y();


$I->wantTo('test the breadcrumb menu');
$I->see('Test2', '.breadcrumb');
$I->see('Antrag', '.breadcrumb');
$I->dontSee('HoesslTo', '.breadcrumb');


$I->wantTo('test the motion view');
$I->gotoMotion();
$I->validateHTML();
$I->validatePa11y();


$I->wantTo('test the amendment view');
$I->gotoAmendment(true, 3, 2);
$I->validateHTML();
$I->validatePa11y();


$I->wantTo('test the login page');
$I->click('#loginLink');
$I->validateHTML();
$I->validatePa11y();


$I->wantTo('test the manager home page');
ManagerStartPage::openBy($I);
$I->validateHTML();
$I->validatePa11y();
