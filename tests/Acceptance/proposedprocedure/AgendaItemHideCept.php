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

$I->click('.agendaEditLink');

$I->seeElement('.agendaEditWidget .item_9 .extraSettings');
$I->clickJS('.agendaEditWidget .item_9 .extraSettings button');
$I->wait(0.5);
$I->uncheckOption('.agendaEditWidget .item_9 .extraSettings .inProposedProcedures input');

$I->clickJS('.agendaEditWidget .btnSave');
$I->wait(1);

$I->wantTo('test that it\'s not visible anymore');

$I->gotoConsultationHome(true, 'laenderrat-to', 'laenderrat-to');
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
$I->click('.agendaEditLink');

$I->seeElement('.agendaEditWidget .item_9 .extraSettings');
$I->clickJS('.agendaEditWidget .item_9 .extraSettings button');
$I->wait(0.5);
$I->seeElement('.agendaEditWidget .item_9 .extraSettings .hasSpeakingList');
$I->dontSeeElement('.agendaEditWidget .item_9 .extraSettings .inProposedProcedures');
