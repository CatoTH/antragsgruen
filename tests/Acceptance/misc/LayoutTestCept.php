<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\ManagerStartPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the home page');
$consultationHome = $I->gotoConsultationHome();
$I->validatePa11y();
$I->validateHTML();

$I->wantTo('test the motion creation form');
$consultationHome->gotoMotionCreatePage();
$I->validateHTML(AcceptanceTester::ACCEPTED_HTML_ERRORS);
$I->validatePa11y();


$I->wantTo('test the breadcrumb menu');
$I->see('Test2', '.breadcrumb');
$I->see('Antrag', '.breadcrumb');
$I->dontSee('HoesslTo', '.breadcrumb');


$I->wantTo('test the motion view');
$I->gotoMotion();
$I->validateHTML(AcceptanceTester::ACCEPTED_HTML_ERRORS);
$I->validatePa11y();


$I->wantTo('test the amendment view');
$I->gotoAmendment(true, 3, 2);
$I->validateHTML(AcceptanceTester::ACCEPTED_HTML_ERRORS);
$I->validatePa11y();


$I->wantTo('test the login page');
$I->click('#loginLink');
$I->validateHTML();
$I->validatePa11y();


$I->wantTo('test the manager home page');
$I->openPage(ManagerStartPage::class);
$I->validateHTML();
$I->validatePa11y();
