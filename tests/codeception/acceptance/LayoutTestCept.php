<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Test the breadcrumb menu');
$consultationHome = $I->gotoStdConsultationHome();
$I->validateHTML();

$consultationHome->gotoMotionCreatePage();

$I->see('Test2', '.breadcrumb');
$I->see('Antrag', '.breadcrumb');
$I->dontSee('HoesslTo', '.breadcrumb');
