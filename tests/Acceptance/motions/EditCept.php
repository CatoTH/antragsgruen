<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable editing of motions');
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->logout();


$I->wantTo('edit an motion');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->click('.myMotionList .motion58');
$I->click('.sidebarActions .edit a');
$I->see('Antrag bearbeiten', 'h1');
$I->executeJS('window.motionText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.motionText = window.motionText + "<p>attach some new text at the end</p>";');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.motionText);');
$I->submitForm('#motionEditForm', [], 'save');

$I->see('Die Änderungen wurden übernommen');
$I->click('#motionConfirmedForm button');
$I->see('attach some new text at the end', '.motionTextHolder');
