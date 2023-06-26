<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('ensure the text doesn\'t get deleted');
$I->loginAndGotoMotionList()->gotoAmendmentEdit(1);
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->click('.sidebarActions .view');
$I->dontSeeElement('del');
$I->seeElement('ul.inserted');
$I->logout();

/*
 * Broken, as original motion sections do not exist in laenderrat-to
$site = $I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');
$I->loginAsStdAdmin();
$I->gotoStdAdminPage(true, 'laenderrat-to', 'laenderrat-to')->gotoMotionList()->gotoAmendmentEdit(168);
$I->wantTo('ensure editing the prefix does not break everything');
$I->fillField('#amendmentTitlePrefix', 'Z-01-009-2');
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->fillField('#amendmentTitlePrefix', 'Z-01-009-1');
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->fillField('#amendmentTitlePrefix', 'Z-01-009-2');
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');
$I->see('Länderrat');
*/
