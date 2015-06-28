<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('change the site into maintainance mode');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->cantSeeCheckboxIsChecked($consultationPage::$maintainanceCheckbox);
$I->checkOption($consultationPage::$maintainanceCheckbox);
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->wantTo('see the maintainance message');
$I->logout();


$I->gotoStdConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->wantTo('try to see the site as a regular user');
$I->loginAsStdUser();
$I->gotoStdConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->wantTo('deactivate the maintainance mode');
$I->logout();
$I->loginAsStdAdmin();
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->seeCheckboxIsChecked($consultationPage::$maintainanceCheckbox);
$I->uncheckOption($consultationPage::$maintainanceCheckbox);
$I->submitForm('#consultationSettingsForm', [], 'save');


$I->wantTo('verify that the maintainance mode is deactivated');
$I->logout();
$I->gotoStdConsultationHome();
$I->see('TEST2', 'h1');
$I->dontSee('Wartungsmodus', 'h1');
