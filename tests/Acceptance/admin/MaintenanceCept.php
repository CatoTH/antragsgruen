<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('change the site into maintenance mode');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontSee('Der Wartungsmodus ist aktiv.', '.consultationwideWarning');
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->cantSeeCheckboxIsChecked($consultationPage::$maintenanceCheckbox);
$I->checkOption($consultationPage::$maintenanceCheckbox);
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->see('Der Wartungsmodus ist aktiv.', '.consultationwideWarning');

$I->wantTo('see the maintenance message');
$I->logout();


$I->gotoConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');
$I->dontSee('Der Wartungsmodus ist aktiv.', '.consultationwideWarning'); // only for admins

$I->wantTo('try to see the site as a regular user');
$I->loginAsStdUser();
$I->gotoConsultationHome(false);
$I->dontSee('TEST2', 'h1');
$I->see('Wartungsmodus', 'h1');
$I->dontSee('Der Wartungsmodus ist aktiv.', '.consultationwideWarning'); // only for admins

$I->wantTo('deactivate the maintenance mode');
$I->logout();
$I->loginAsStdAdmin();
$I->see('Der Wartungsmodus ist aktiv.', '.consultationwideWarning');
$consultationPage = $I->gotoStdAdminPage()->gotoConsultation();

$I->seeCheckboxIsChecked($consultationPage::$maintenanceCheckbox);
$I->uncheckOption($consultationPage::$maintenanceCheckbox);
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->dontSee('Der Wartungsmodus ist aktiv.', '.consultationwideWarning');

$I->wantTo('verify that the maintenance mode is deactivated');
$I->logout();
$I->gotoConsultationHome();
$I->see('TEST2', 'h1');
$I->dontSee('Wartungsmodus', 'h1');
