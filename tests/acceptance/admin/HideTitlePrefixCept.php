<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->see('A2');
$I->see('Ä1');

$I->wantTo('disable title prefixes');
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoConsultation();
$I->checkOption('#hideTitlePrefix');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->dontSee('A2');

$I->click('.motionLink2');
$I->dontSee('A2');
$I->click('.amendmentCreate a');
$I->dontSee('A2');
$I->gotoConsultationHome();
$I->click('.amendment1');
$I->dontSee('A2');
