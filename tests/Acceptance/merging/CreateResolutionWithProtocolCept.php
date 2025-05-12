<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->seeElement('.motionListStd .motionLink2');
$I->dontSeeElement('.resolutionList');

$I->gotoMotion();
$I->dontSeeElement('.sidebarActions .mergeamendments');

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->checkOption('.toMergeAmendments #markAmendment1');
$I->click('.mergeAllRow .btn-primary');
$I->wait(0.5);
$I->see('Oamoi a Maß', '.ice-ins');

$I->executeJS('CKEDITOR.instances.protocol_text_wysiwyg.setData("<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>")');
$I->checkOption("//input[@name='protocol_public'][@value='1']");

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);
$I->submitForm('.motionMergeForm', [], 'save');


$I->wantTo('confirm the changes');
$I->see('Oamoi a Maß');
$I->dontSee('Oamoi a Maß', '.inserted');
$I->executeJS('$("input[name=diffStyle][value=diff]").parents(".btn").click()');
$I->see('Oamoi a Maß', '.inserted');


$I->wantTo('choose the resolution');
$I->click("//input[@name='newStatus'][@value='resolution_preliminary']");
$I->seeElement('#newInitiator');
$I->seeElement('#dateResolution');
$I->fillField('#newInitiator', 'Mitgliedervollversammlung');
$I->fillField('#dateResolution', '23.04.2017');


$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('confirm the resolution');
$I->dontSee('A2neu', 'h1');
$I->see('O’zapft is!', 'h1');
$I->see('Oamoi a Maß');

$I->see('Beschluss durch', '.motionDataTable');
$I->see('Beschlossen am', '.motionDataTable');
$I->see('Mitgliedervollversammlung', '.motionDataTable');
$I->see('Beschluss (vorläufig)', '.motionDataTable');

$I->see('Beschlusstext', 'h2');

$I->dontSeeElement('.protocolHolder');
$I->dontSee('So Long, and Thanks for All the Fish');
$I->clickJS('.motionProtocol .protocolOpener');
$I->see('So Long, and Thanks for All the Fish', '.protocolHolder');


$I->wantTo('see the diff view');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->click('.changesLink a');
$I->see('Oamoi a Maß', '.motionChangeView.section2 .inserted');
$I->dontSeeElement('.motionChangeView .section3');
$I->see('So Long, and Thanks for All the Fish', '.protocolHolder');

$I->gotoConsultationHome();
$I->seeElement('.resolutionList');
$I->seeElement('.resolutionList .motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
$I->seeElement('.motionListStd .motionLink2');
