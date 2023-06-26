<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('check that the basic functuanality works');
$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->loginAsFixedDataUser();

$I->seeInField('#initiatorPrimaryName', 'Fixed Data');
$I->seeInField('#initiatorOrga', 'MotionTools');
$readonly = $I->executeJS('return $("#initiatorPrimaryName").attr("readonly")');
$I->assertEquals('readonly', $readonly);

$I->checkOption('#personTypeOrga');
$readonly = $I->executeJS('return $("#initiatorPrimaryName").attr("readonly")');
$I->assertNotEquals('readonly', $readonly);

$I->checkOption('#personTypeNatural');
$readonly = $I->executeJS('return $("#initiatorPrimaryName").attr("readonly")');
$I->assertEquals('readonly', $readonly);


$I->wantTo('submit a motion with a fake name');

$I->executeJS('$("#initiatorPrimaryName").val("Some fake name")');
$I->seeInField('#initiatorPrimaryName', 'Some fake name');

$I->fillField(['name' => 'sections[1]'], 'Test motion');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');

$I->submitForm('#motionEditForm', [], 'save');

$I->see('Test motion');
$I->dontSee('Some fake name');
$I->see('Fixed Data (MotionTools)');
