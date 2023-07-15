<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->dontSeeElement('#consultationPath');
$I->click('.urlPathHolder .shower a');
$I->seeElement('#consultationPath');
$I->fillField('#consultationPath', '38');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->seeInField('#consultationPath', '38');

$I->gotoConsultationHome(true, 'stdparteitag', '38');
$I->see('Test2', 'h1');

$I->gotoConsultationHome(false);
$I->dontSee('Test2', 'h1');
$I->see('Die angegebene Veranstaltung wurde nicht gefunden');
