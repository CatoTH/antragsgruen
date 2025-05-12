<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a progress report motion type');

$consultation = $I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->checkOption('.presetProgress');
$I->fillField('#typeTitleSingular', 'Progress report');
$I->fillField('#typeTitlePlural', 'Progress reports');
$I->fillField('#typeCreateTitle', 'Create');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->wantTo('remove the second text from motions to make it compatible with progress reports');
$I->gotoStdAdminPage();
$I->click('.motionType1');
$I->clickJS('.section4 .remover');
$I->seeBootboxDialog('Wirklich löschen?');
$I->acceptBootboxConfirm();
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeElement('.section3');
$I->dontSeeElement('.section4');


$I->wantTo('merge the amendments and create the report');
$I->gotoMotion();
$I->click('.sidebarActions .mergeamendments a');
$I->click('.mergeAllRow .btn-primary');
$I->wait(0.5);
$I->see('Oamoi a Maß', '.ice-ins');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);
$I->submitForm('.motionMergeForm', [], 'save');

$I->click("//input[@name='newStatus'][@value='resolution_final']");
$I->seeElement('#newInitiator');
$I->seeElement('#dateResolution');
$I->seeElement('#motionType');
$I->fillField('#newInitiator', 'Mitgliedervollversammlung');
$I->fillField('#dateResolution', '23.04.2017');
$I->selectOption('#motionType', AcceptanceTester::FIRST_FREE_MOTION_TYPE);

$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('confirm the progress report was created');
$I->see('O’zapft is!', 'h1');
$I->see('Beschluss durch', '.motionDataTable');
$I->see('Mitgliedervollversammlung', '.motionDataTable');

$I->see('Beschlusstext', 'h2');
$I->seeElement('#section_53');
$I->see('Sachstand', 'h2');

$I->wantTo('not see the progress report as regular user');
$I->logout();
$I->see('Beschlusstext', 'h2');
$I->dontSee('Sachstand', 'h2');


$I->wantTo('edit the progress report as admin');
$I->loginAsProgressAdmin();
$I->see('Sachstand', 'h2');
$I->clickJS('.editorialEditForm .editCaller');
$I->wait(0.5);

$sectionId = 'section_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 2);
$I->executeJS('CKEDITOR.instances.' . $sectionId . '_content.setData("<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>")');
$I->fillField('#' . $sectionId . ' .metadataEdit input.author', 'You know who');
$I->clickJS('.saveRow .submitBtn');
$I->wait(0.5);
$I->see('You know who, Heute');


$I->wantTo('see the progress report as user');
$I->logout();
$I->see('So Long, and Thanks for All the Fish', 'blockquote');
$I->see('You know who, Heute');
$I->dontSeeElement('.editorialEditForm .editCaller');
