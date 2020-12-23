<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('disable the breadcrumb');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();

$I->dontSeeCheckboxIsChecked('#hasSpeechLists');
$I->dontSeeElement('.quotas');
$I->executeJS('$("#hasSpeechLists").prop("checked", true).trigger("change");');
$I->wait(0.1);
$I->seeElement('.quotas');
$I->checkOption('#hasMultipleSpeechLists');

$page->saveForm();

$I->gotoConsultationHome();

$I->see('Redeliste', '.currentSpeechInline');
$I->see('Frauen', '.waitingSubqueues');
$I->see('Offen / Männer', '.waitingSubqueues');
