<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Test the breadcrumb menu');
$consultationHome = $I->gotoConsultationHome();
$I->validatePa11y();
$I->validateHTML();

$consultationHome->gotoMotionCreatePage();
// No validateHTML, as CKEditor modifies the HTML code in a way that makes it invalid
$I->validatePa11y();

$I->see('Test2', '.breadcrumb');
$I->see('Antrag', '.breadcrumb');
$I->dontSee('HoesslTo', '.breadcrumb');

$I->gotoMotion();
$I->validateHTML();
$I->validatePa11y();

$I->gotoAmendment(true, 3, 2);
$I->validateHTML();
$I->validatePa11y();
