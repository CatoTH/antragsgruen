<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->click('.editThemeLink');

$I->assertEquals('rgb(109, 126, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".col-md-9.well")[0])["border-top-left-radius"]'));


$I->fillField('#stylesheet-contentBorderRadius', '5');
$I->executeJS('$("#stylesheet-menuLink").val("#FF0000");');
$I->submitForm('.themingForm', [], 'save');

$I->assertEquals('rgb(255, 0, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('5px', $I->executeJS('return getComputedStyle($(".col-md-9.well")[0])["border-top-left-radius"]'));


$I->gotoStdAdminPage()->gotoConsultation();
$I->seeCheckboxIsChecked('.customThemeSelector input');
$I->executeJS('$("input[value=layout-classic").parents("label").click()');
$I->checkOption("//input[@name='siteSettings[siteLayout]'][@value='layout-classic']");
$I->submitForm('#consultationSettingsForm', [], 'save');


$I->assertEquals('rgb(109, 126, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".col-md-9.well")[0])["border-top-left-radius"]'));
