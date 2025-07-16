<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the consultation page');
$I->gotoConsultationHome(true, 'parteitag', 'parteitag');

$I->see('Parteitag', 'h1');
$I->dontSeeElementInDOM('.agendaEditLink');
$I->see('0. Tagesordnung', '.motionListWithinAgenda');
$I->see('1. 1. Vorsitzende', '.motionListWithinAgenda');
$I->see('3. Sonstiges', '.motionListWithinAgenda');
$I->dontSee('1. Sonstiges', '.motionListWithinAgenda');
$I->see('Bewerben', '#agendaitem_3 > div > h3');
$I->see('Antrag stellen', '#agendaitem_6 > div > h3');

$I->wantTo('edit the agenda a bit');

$I->loginAsStdAdmin();
$I->see('Parteitag', 'h1');
$I->see('Tagesordnung', '.motionListWithinAgenda');
$I->seeElementInDOM('.agendaEditLink');
$I->click('.agendaEditLink');

$I->wantTo('reorder the list');
$I->wait(0.3);
$I->seeElement('.agendaEditWidget');

// Move last item ("Sonstiges") to first location, and set code to "10."
// Remove code from first item
// Add another item at the end
$listData = json_decode($I->executeJs('return JSON.stringify(agendaWidget.$refs["agenda-edit-widget"].getAgendaTest())'), true);
$lastItem = array_pop($listData);
$lastItem['code'] = '10.';
$listData[0]['code'] = null;
array_unshift($listData, $lastItem);
$listData[] = [
    "type" => "item",
    "code" => null,
    "title" => "More motions",
    "settings" => [
        "has_speaking_list" => false,
        "in_proposed_procedures" => true,
        "motion_types" => [5],
    ],
    "children" => [],
];
$newListData = json_encode($listData);
$I->executeJs('agendaWidget.$refs["agenda-edit-widget"].setAgendaTest(' . $newListData . ');');
$I->wait(0.3);

$newFirstTitle = $I->executeJs('return $(".agendaEditWidget > ul > li:first .titleCol").val()');
$I->assertSame("Sonstiges", $newFirstTitle);

$I->clickJS('.agendaEditWidget .btnSave');
$I->wait(1);

$I->wantTo('check if my chenges are saved');
$I->click('.backHomeLink');
$I->see('14. More motions', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3');
$I->see('Antrag stellen', '#agendaitem_' . AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID . ' > div > h3');


$I->wantTo('add a date and time');
$I->click('.agendaEditLink');

$I->dontSeeElement('.datetimepicker.time');
$I->clickJS('.showTimeSelector');
$I->seeElement('.datetimepicker.time');

$listData = json_decode($I->executeJs('return JSON.stringify(agendaWidget.$refs["agenda-edit-widget"].getAgendaTest())'), true);
$listData[0]['time'] = '17:30';
array_unshift($listData, [
    "type" => "date_separator",
    "date" => "2020-02-02",
    "title" => "",
    "settings" => [
        "has_speaking_list" => false,
        "in_proposed_procedures" => true,
        "motion_types" => [],
    ],
    "children" => [],
]);
$newListData = json_encode($listData);
$I->executeJs('agendaWidget.$refs["agenda-edit-widget"].setAgendaTest(' . $newListData . ');');
$I->wait(0.3);


$I->clickJS('.agendaEditWidget .btnSave');
$I->wait(1);

$I->wantTo('check if my chenges are saved');
$I->click('.backHomeLink');

$I->see('Sonntag, 2. Februar 2020', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' h3');
$I->see('17:30', '#agendaitem_7 .time');


$I->wantTo('delete the two modified items');

$I->click('.agendaEditLink');
$I->seeElement('.datetimepicker.time');

$listData = json_decode($I->executeJs('return JSON.stringify(agendaWidget.$refs["agenda-edit-widget"].getAgendaTest())'), true);
array_shift($listData);
array_pop($listData);
$newListData = json_encode($listData);
$I->executeJs('agendaWidget.$refs["agenda-edit-widget"].setAgendaTest(' . $newListData . ');');
$I->wait(0.3);

$I->clickJS('.agendaEditWidget .btnSave');
$I->wait(1);

$I->wantTo('check if my chenges are saved');
$I->click('.backHomeLink');

$I->dontSee('Sonntag, 2. Februar 2020');
$I->dontSee('More motions');
$I->see('10. Sonstiges');
