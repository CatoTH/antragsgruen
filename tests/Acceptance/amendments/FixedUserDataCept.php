<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('check that the basic functuanality works');
$I->gotoConsultationHome()->gotoAmendmentCreatePage();
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


$I->wantTo('submit an amendment with a fake name');

$I->executeJS('$("#initiatorPrimaryName").val("Some fake name")');
$I->seeInField('#initiatorPrimaryName', 'Some fake name');

$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Änderungsantrag bestätigen');
$I->dontSee('Some fake name');
$I->see('Fixed Data (MotionTools)');
