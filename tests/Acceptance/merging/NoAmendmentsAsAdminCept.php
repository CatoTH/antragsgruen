<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('use merging for a motion without amendments');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoMotion(true, 58);
$I->click('.sidebarActions .mergeamendments');
$I->dontSeeElement('.motionMergeInit');
$I->seeElement('.motionMergeForm');
$I->dontSeeElement('.motionData .alert-info');
$I->dontSeeElement('.newAmendments');
$I->dontSeeElement('.mergeActionHolder');

$I->wait(1);
$I->executeJS('CKEDITOR.instances.sections_2_0_wysiwyg.setData("<p>An updated version of this motion</p>");');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...

$I->see('An updated version of this motion', '#sections_2_0_wysiwyg');
$I->submitForm('.motionMergeForm', [], 'save');

$I->see('An updated version of this motion');
$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');

$I->see('An updated version of this motion', '.motionTextHolder1');
