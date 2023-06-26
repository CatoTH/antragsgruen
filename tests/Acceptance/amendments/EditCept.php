<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable editing of amendments');
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->logout();


$I->wantTo('edit an amendment');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->click('.myAmendmentList .amendment2');
$I->click('.sidebarActions .edit a');
$I->see('Änderungsantrag zu A3: Textformatierungen bearbeiten', 'h1');
$I->executeJS('window.amendText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.amendText = window.amendText + "<p>attach some new text at the end</p>";');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.amendText);');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Die Änderungen wurden übernommen');
$I->click('#motionConfirmedForm button');
$I->see('attach some new text at the end', 'p.inserted');
