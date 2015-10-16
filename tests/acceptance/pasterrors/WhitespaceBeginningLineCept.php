<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a motion with a whitespace at the beginning');
$create = $I->gotoConsultationHome()->gotoMotionCreatePage();
$create->fillInValidSampleData();
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong> Test</strong></p>");');
$create->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');
