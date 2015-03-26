<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('See the site with default settings');
$I->gotoStdConsultationHome();

$I->wantTo('Change the site into maintainance mode');
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('#consultationLink');

$I->cantSeeCheckboxIsChecked('#maintainanceMode');
$I->checkOption('#maintainanceMode');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->wantTo('See the maintainance message');
$I->logout();


$I->gotoStdConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->wantTo('Try to see the site as a regular user');
$I->loginAsStdUser();
$I->gotoStdConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->wantTo('Deactivate the maintainance mode');
$I->logout();
$I->loginAsStdAdmin();
$I->gotoStdConsultationHome();
$I->click('#adminLink');
$I->click('#consultationLink');

$I->seeCheckboxIsChecked('#maintainanceMode');
$I->uncheckOption('#maintainanceMode');
$I->submitForm('#consultationSettingsForm', [], 'save');


$I->wantTo('Verify that the maintainance mode is deactivated');
$I->logout();
$I->gotoStdConsultationHome();
$I->see('TEST2', 'h1');
$I->dontSee('Wartungsmodus', 'h1');
