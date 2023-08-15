<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable speech lists with Point of Orders');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline');

$I->loginAsStdAdmin();
$I->gotoStdAdminPage();
$I->click('.speechAdminLink');
$I->wait(0.3);
$I->seeElement('.settingsActive .inactive');
$I->clickJS('.settingsActive button');
$I->wait(0.2);
$I->dontSeeElement('.settingsActive .inactive');

$I->clickJS('.settingOpen input');
$I->wait(0.2);
$I->clickJS('.settingOpenPoo input');
$I->wait(0.2);


$I->wantTo('create a regular speaking list entry and a point of order');
$I->seeElement('.subqueues .empty');
$I->clickJS('.subqueueAdder .adderOpener');
$I->fillField('#subqueueAdderName-1', 'Regular speech');
$I->clickJS('.subqueueAdder form button');
$I->wait(0.2);
$I->dontSeeElement('.subqueues .empty');
$I->see('Regular speech', '.slotPlaceholder');

$I->gotoConsultationHome();
$I->logout();
$I->wait(0.1);
$I->seeElement('.applyOpener');
$I->clickJS('.applyOpenerPoo');
$I->fillField('#speechRegisterName-1', 'My Point');
$I->clickJS('.waitingSingle form button');
$I->wait(0.1);
$I->see('Warteliste: 2', '.waitingSingle');
$I->see('Regular speech', '.waitingSingle');
$I->see('My point', '.waitingSingle');
$I->see('GO-Antrag', '.waitingSingle .label');


$I->wantTo('see the point of order up next and delete it again as admin');
$I->loginAsStdAdmin();
$I->gotoStdAdminPage();
$I->click('.speechAdminLink');
$I->wait(0.3);
$I->see('My point', '.slotPlaceholder');
$I->see('GO-Antrag', '.slotPlaceholder .label');
$I->see('GO-Antrag', '.subqueueItems .subqueueItem:nth-child(2)');
$I->see('Regular speech', '.subqueueItems .subqueueItem:nth-child(4)');

$I->clickJS('.subqueueItems .subqueueItem:nth-child(2) .operationDelete');
$I->wait(0.3);
$I->dontSee('GO-Antrag', '.waitingSingle');
$I->see('Regular speech', '.slotPlaceholder');
