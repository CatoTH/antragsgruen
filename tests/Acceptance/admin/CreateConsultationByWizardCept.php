<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\SiteHomePage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultationCreatePage();

$I->see('Standard-Veranstaltung', '.consultation1');

$I->wantTo('create a new consultation');
$I->fillField('#newTitle', 'Neue Veranstaltung 1');
$I->fillField('#newShort', 'NeuKurz');
$I->fillField('#newPath', 'neukurz');
$I->uncheckOption('#newSetStandard');

$I->dontSeeElement('.settingsTypeWizard');
$I->seeElement('.settingsTypeTemplate');
$I->checkOption('#settingsTypeWizard');
$I->dontSeeElement('.settingsTypeTemplate');
$I->seeElement('.settingsTypeWizard');


$I->see('Welche Bestandteile soll die Seite haben?', '#panelFunctionality');

$I->seeElement('.checkbox-label.value-motion.active');
$I->clickJS('.checkbox-label.value-agenda');
$I->click('#panelFunctionality button.btn-next');

$I->click('#panelSingleMotion .value-0');
$I->click('#panelSingleMotion button.btn-next');

$I->click('#panelMotionWho .value-3');
$I->click('#panelMotionWho button.btn-next');

$I->click('#panelMotionDeadline .value-1');
$I->fillField('#panelMotionDeadline .value-1 .date input', '30.12.2028 20:00');
$I->click('#panelMotionDeadline button.btn-next');

$I->click('#panelMotionScreening .value-1');
$I->click('#panelMotionScreening button.btn-next');

$I->click('#panelNeedsSupporters .value-1');
$I->fillField('#panelNeedsSupporters .value-1 .description input', 1);
$I->click('#panelNeedsSupporters button.btn-next');

$I->click('#panelHasAmendments .value-1');
$I->click('#panelHasAmendments button.btn-next');

$I->click('#panelAmendSinglePara .value-1');
$I->click('#panelAmendSinglePara button.btn-next');

$I->click('#panelAmendWho .value-3');
$I->click('#panelAmendWho button.btn-next');

$I->click('#panelAmendDeadline .value-1');
$I->fillField('#panelAmendDeadline .value-1 .date input', '30.11.2026 20:00');
$I->click('#panelAmendDeadline button.btn-next');

$I->click('#panelAmendScreening .value-1');
$I->click('#panelAmendScreening button.btn-next');

$I->click('#panelComments .value-1');
$I->click('#panelComments button.btn-next');

$I->click('#panelOpenNow .value-0');
$I->click('#panelOpenNow button.btn-next');

$I->click('#panelSiteData button.btn-primary');


$I->see('Die neue Veranstaltung wurde angelegt.');
$I->see('Neue Veranstaltung 1', '.consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID);
$I->see('Standard-Veranstaltung', '.consultation1');




$I->wantTo('check that the settings were set correctly');

$I->gotoStdAdminPage('stdparteitag', 'neukurz')->gotoMotionTypes(AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->dontSeeCheckboxIsChecked('#deadlineFormTypeComplex');
$I->seeInField('#typeSimpleDeadlineMotions', '30.12.2028 20:00');
$I->seeInField('#typeSimpleDeadlineAmendments', '30.11.2026 20:00');

$I->gotoStdAdminPage('stdparteitag', 'neukurz')->gotoConsultation();
$I->seeCheckboxIsChecked('#maintenanceMode');


$I->wantTo('set the new consultation as standard');

$I->gotoStdAdminPage('stdparteitag', 'neukurz')->gotoConsultationCreatePage();


$I->click('.consultation' . AcceptanceTester::FIRST_FREE_CONSULTATION_ID . ' .stdbox button');
$I->see('Die Veranstaltung wurde als Standard-Veranstaltung festgelegt.');

$I->openPage(SiteHomePage::class, [
    'subdomain' => 'stdparteitag'
]);
$I->see('Neue Veranstaltung 1', 'h1');

$I->see('Wahl: 1. Vorsitzende');
$I->see('3. Sonstiges');


$I->logout();
$I->dontSee('Neue Veranstaltung 1', 'h1');
$I->see('Wartungsmodus', 'h1');
