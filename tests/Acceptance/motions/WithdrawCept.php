<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('withdraw the motion I created before');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->gotoMotion(true, 2);


$I->click('.sidebarActions .withdraw a');
$I->see('Willst du diesen Antrag wirklich zurückziehen?');
$I->submitForm('.withdrawForm', [], 'withdraw');
$I->see('Der Antrag wurde zurückgezogen.');
$I->see('Zurückgezogen', '.motionDataTable .statusRow');
$I->dontSeeElement('.sidebarActions .withdraw a');
$I->gotoConsultationHome();
$I->seeElement('.motionRow2.withdrawn');
