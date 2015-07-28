<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure the supporter-warning appears for natural persons');

$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->click('#sidebar .createMotion');

$I->fillField(['name' => 'sections[20]'], 'Testantrag');
$I->executeJS('CKEDITOR.instances.sections_21_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_22_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
$I->fillField(['name' => 'Initiator[name]'], 'Mein Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#motionEditForm', [], 'save');

$I->wait(1);

$I->see('Es müssen mindestens 19 UnterstützerInnen angegeben werden', '.bootbox');
$I->click('.bootbox .btn-primary');

$I->wait(1);



$I->wantTo('make sure it does not appear for organizations');

$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#motionEditForm', [], 'save');

$I->wait(1);
$I->dontSee('Es müssen mindestens 19 UnterstützerInnen angegeben werden', '.bootbox');
