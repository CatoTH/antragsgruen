<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('see the activated proposed procedure');
$I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');

$I->see('Zeitpolitik', '#agendaitem_9');
$I->see('Zeitpolitik', '.motionLink64');
$I->seeElement('#sidebar #proposedProcedureLink');
$I->click('#sidebar #proposedProcedureLink');

$I->see('Abstimmung: Zeitpolitik');
$I->see('Z-01');
$I->see('Z-01-115-1');

$I->see('Abstimmung: Tagesordnung');
$I->see('F-01');
$I->see('W-01');

$I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');
$I->loginAsGlobalAdmin();

$I->wantTo('deactivate Zeitpolitik from the proposed procedure');

$I->executeJS('$("#agendaitem_9 > div > h3 > .editAgendaItem").trigger("click");');
$I->wait(0.3);
$I->seeElement('#agendaitem_9 > div > .agendaItemEditForm');
$I->seeElement('#agendaitem_9 > div > .agendaItemEditForm > .extraSettings');
$I->executeJS('$("#agendaitem_9 > div > .agendaItemEditForm > .extraSettings .dropdown-toggle").trigger("click");');
$I->wait(0.5);
$I->checkOption('#agendaitem_9 > div > .agendaItemEditForm > .extraSettings .inProposedProcedures');
$I->executeJS('$("#agendaitem_9 > div > .agendaItemEditForm").trigger("submit");');

$I->wantTo('test that it\'s not visible anymore');

$I->click('#sidebar #proposedProcedureLink');

$I->dontSee('Abstimmung: Zeitpolitik');
$I->dontSee('Z-01');
$I->dontSee('Z-01-115-1');
$I->see('Abstimmung: Tagesordnung');
$I->see('F-01');
$I->see('W-01');


$I->wantTo('disable proposed procedures and see that the feature is deactivated then');

$page = $I->gotoStdAdminPage('laenderrat-to', 'laenderrat-to')->gotoMotionTypes(9);
$I->uncheckOption('#typeProposedProcedure');
$page->saveForm();

$I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');
$I->wait(0.3);
$I->executeJS('$("#agendaitem_9 > div > h3 > .editAgendaItem").trigger("click");');
$I->wait(0.3);
$I->seeElement('#agendaitem_9 > div > .agendaItemEditForm > .extraSettings');
$I->executeJS('$("#agendaitem_9 > div > .agendaItemEditForm > .extraSettings .dropdown-toggle").trigger("click");');
$I->wait(0.5);
$I->seeElement('#agendaitem_9 > div > .agendaItemEditForm > .extraSettings .hasSpeakingList');
$I->dontSeeElement('#agendaitem_9 > div > .agendaItemEditForm > .extraSettings .inProposedProcedures');
