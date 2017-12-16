<?php

use app\tests\_pages\ConsultationHomePage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the consultation page');

$page = ConsultationHomePage::openBy(
    $I,
    [
        'subdomain'        => 'parteitag',
        'consultationPath' => 'parteitag',
    ]
);

$I->see('Parteitag', 'h1');
$I->dontSeeElementInDOM('.moveHandle');
$I->see('0. Tagesordnung', '.motionListWithinAgenda');
$I->see('1. Wahl: 1. Vorsitzende', '.motionListWithinAgenda');
$I->see('3. Sonstiges', '.motionListWithinAgenda');
$I->dontSee('1. Sonstiges', '.motionListWithinAgenda');
$I->see('Bewerben', '#agendaitem_3 > div > h3');
$I->see('Antrag stellen', '#agendaitem_6 > div > h3');

$I->wantTo('edit the agenda a bit');

$I->loginAsStdAdmin();
$I->see('Parteitag', 'h1');
$I->seeElementInDOM('.moveHandle');
$I->see('Tagesordnung', '.motionListWithinAgenda');
$I->dontSeeElement('.agendaItemEditForm');
$I->dontSeeElement('#agendaEditSavingHolder');

$I->executeJS('$(".agendaListEditing").find("> li.agendaItem").last().prependTo(".agendaListEditing");');
$I->executeJS('$("ol.motionListWithinAgenda").trigger("antragsgruen:agenda-change");');
$I->see('1. Sonstiges', '.motionListWithinAgenda');

$I->executeJS('$(".agendaListEditing").find("> li").eq(2).find("> ol").children().last().find("a").click();');
$I->seeElement('.agendaItemEditForm');
$I->seeElement('#agendaitem_-1 .agendaItemEditForm .code');
$I->fillField('#agendaitem_-1 .agendaItemEditForm .title', 'More motions');
$I->selectOption('#agendaitem_-1 .agendaItemEditForm .motionType', '5');
$I->seeElement('#agendaEditSavingHolder');
$I->submitForm('#agendaEditSavingHolder', [], 'saveAgenda');



$I->wantTo('check if my chenges are saved');
$I->dontSeeElement('.agendaItemEditForm');
$I->dontSeeElement('#agendaEditSavingHolder');
$I->see('4. More Motions', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID. ' > div > h3');
$I->see('Antrag stellen', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID. ' > div > h3');


$I->wantTo('further change the agenda a bit');
$I->see('Bewerben', '#agendaitem_5 > div > h3');
$I->executeJS('$(".motionListWithinAgenda").children().eq(2).find("> ol").children().eq(2).insertAfter($(".motionListWithinAgenda").children().eq(0));');
$I->executeJS('$("ol.motionListWithinAgenda").trigger("antragsgruen:agenda-change");');
$I->see('2. Wahl: Schatzmeister', '.motionListWithinAgenda');
$I->see('3. More Motions', '.motionListWithinAgenda');
$I->see('2. Anträge', '.motionListWithinAgenda');

$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > h3 .editAgendaItem").click();');
$I->fillField('#agendaitem_5 .agendaItemEditForm .title', 'Sonstwas');
$I->selectOption('#agendaitem_5 .agendaItemEditForm .motionType', '0');
$I->submitForm('#agendaEditSavingHolder', [], 'saveAgenda');

$I->dontSee('Bewerben', '#agendaitem_5 > div > h3');
$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > h3 .editAgendaItem").click()');
$I->seeInField('#agendaitem_5 .agendaItemEditForm .title', 'Sonstwas');
$I->seeOptionIsSelected('#agendaitem_5 .agendaItemEditForm .motionType', 'Keine Anträge');
$I->submitForm('#agendaitem_5 .agendaItemEditForm', [], '');



$I->wantTo('delete the two modified items');

$I->see('Sonstwas');
$I->see('More motions');
$I->moveMouseOver('#agendaitem_5 > div > h3');
$I->click('#agendaitem_5 > div > h3 .delAgendaItem');
$I->seeBootboxDialog('Diesen Tagesordnungspunkt mitsamit Unterpunkten löschen?');
$I->acceptBootboxConfirm();
$I->moveMouseOver('#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID. ' > div > h3');
$I->click('#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID. ' > div > h3 .delAgendaItem');
$I->seeBootboxDialog('Diesen Tagesordnungspunkt mitsamit Unterpunkten löschen?');
$I->acceptBootboxConfirm();
$I->dontSee('Sonstwas');
$I->dontSee('More motions');

$I->submitForm('#agendaEditSavingHolder', [], 'saveAgenda');

$I->dontSee('Sonstwas');
$I->dontSee('More motions');
