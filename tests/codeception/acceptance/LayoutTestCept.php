<?php

use tests\codeception\_pages\MotionCreatePage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('Test the breadcrumb menu');
$I->gotoStdConsultationHome()->gotoMotionCreatePage();

$I->see('Test2', '.breadcrumb');
$I->see('Antrag', '.breadcrumb');
$I->dontSee('HoesslTo', '.breadcrumb');
