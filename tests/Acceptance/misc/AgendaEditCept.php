<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the consultation page');
$I->gotoConsultationHome(true, 'parteitag', 'parteitag');

$I->see('Parteitag', 'h1');
$I->dontSeeElementInDOM('.moveHandle');
$I->see('0. Tagesordnung', '.motionListWithinAgenda');
$I->see('1. 1. Vorsitzende', '.motionListWithinAgenda');
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

$I->wantTo('reorder the list');
$I->executeJS('$(".agendaListEditing").find("> li.agendaItem").last().prependTo(".agendaListEditing");');
$I->executeJS('$("ol.motionListWithinAgenda").trigger("antragsgruen:agenda-change");');
$I->see('1. Sonstiges', '.motionListWithinAgenda');

$I->executeJS('$(".agendaListEditing").find("> li").eq(2).find("> ol").children().last().find("a").click();');
$I->seeElement('.agendaItemEditForm');
$I->seeElement('#agendaitem_-1 .agendaItemEditForm .code');
$I->fillField('#agendaitem_-1 .agendaItemEditForm .title input', 'More motions');
$I->selectOption('#agendaitem_-1 .agendaItemEditForm .motionType select', '5');
$I->executeJS('$("#agendaitem_-1 > div .agendaItemEditForm").trigger("submit");');
$I->wait(0.3);

$I->wantTo('check if my chenges are saved');
$I->dontSeeElement('.agendaItemEditForm');
$I->see('1.4. More Motions', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3');
$I->see('Antrag stellen', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3');


$I->wantTo('further change the agenda a bit');
$I->see('Bewerben', '#agendaitem_5 > div > h3');
$I->executeJS('$(".motionListWithinAgenda").children().eq(2).find("> ol").children().eq(2).insertAfter($(".motionListWithinAgenda").children().eq(0));');
$I->executeJS('$("ol.motionListWithinAgenda").trigger("antragsgruen:agenda-reordered");');
$I->wait(0.3);

$I->see('2. Schatzmeister*in', '.motionListWithinAgenda');
$I->see('1.3. More Motions', '.motionListWithinAgenda');
$I->see('2. Anträge', '.motionListWithinAgenda');

$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > h3 .editAgendaItem").click();');
$I->fillField('#agendaitem_5 .agendaItemEditForm .title input', 'Sonstwas');
$I->selectOption('#agendaitem_5 .agendaItemEditForm .motionType select', '0');
$I->executeJS('$("#agendaitem_5 > div .agendaItemEditForm").trigger("submit");');
$I->wait(0.3);

$I->dontSee('Bewerben', '#agendaitem_5 > div > h3');
$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > h3 .editAgendaItem").click()');
$I->seeInField('#agendaitem_5 .agendaItemEditForm .title input', 'Sonstwas');
$I->seeOptionIsSelected('#agendaitem_5 .agendaItemEditForm .motionType select', 'Keine Anträge');
$I->executeJS('$("#agendaitem_5 > div .agendaItemEditForm").trigger("submit");');
$I->wait(0.3);

$I->wantTo('add a date');
$I->executeJS('$(".motionListWithinAgenda .agendaItemAdder .addDate").click()');
$I->executeJS('$("#agendaitem_-1 .agendaDateEditForm .dateSelector").data("DateTimePicker").date(moment("2020-02-02", "YYYY-MM-DD"));');
$I->seeInField('#agendaitem_-1 .agendaDateEditForm .dateSelector input', 'Sonntag, 2. Februar 2020');
$I->executeJS('$("#agendaitem_-1 .agendaDateEditForm .ok button").click();');
$I->wait(0.3);

$I->see('Sonntag, 2. Februar 2020', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' h3');
$I->executeJS('$(".agendaListEditing").find("> li.agendaItem").last().prependTo(".agendaListEditing");');
$I->executeJS('$("ol.motionListWithinAgenda").trigger("antragsgruen:agenda-reordered");');
$I->wait(0.3);

$I->see('Sonntag, 2. Februar 2020', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' h3');
$I->dontSeeElement('#agendaitem_-1');

$I->wantTo('add a time');
$I->dontSeeElement('#agendaitem_7 .time');
$I->dontSeeElement('#agendaitem_5 .time');
$I->seeElementInDOM('.motionListWithinAgenda.noShowTimes');
$I->executeJS('$(".motionListWithinAgenda .agendaItemAdder .showTimes input").trigger("click")');
$I->seeElementInDOM('.motionListWithinAgenda.showTimes');

$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > h3 .editAgendaItem").click()');
$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > form .time").data("DateTimePicker").date(moment("17:30", "LT"));');
$I->executeJS('$(".motionListWithinAgenda").children().eq(1).find("> div > form .ok button").click();');
$I->wait(0.3);

$I->see('17:30', '#agendaitem_7 .time');
$I->see('Sonntag, 2. Februar 2020', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' h3');


$I->wantTo('delete the two modified items');

$I->see('Sonstwas');
$I->see('More motions');
$I->moveMouseOver('#agendaitem_5 > div > h3');
$I->click('#agendaitem_5 > div > h3 .delAgendaItem');
$I->seeBootboxDialog('Diesen Tagesordnungspunkt mitsamit Unterpunkten löschen?');
$I->acceptBootboxConfirm();
$I->moveMouseOver('#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3');
$I->executeJS('$("#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3 .delAgendaItem").trigger("click")');
$I->seeBootboxDialog('Diesen Tagesordnungspunkt mitsamit Unterpunkten löschen?');
$I->acceptBootboxConfirm();
$I->dontSee('Sonstwas');
$I->dontSee('More motions');


$I->gotoConsultationHome(true, 'parteitag', 'parteitag');

$I->dontSee('Sonstwas');
$I->dontSee('More motions');
$I->see('1. Sonstiges');
