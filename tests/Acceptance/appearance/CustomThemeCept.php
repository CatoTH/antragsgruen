<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->click('.editThemeLink');

$I->wantTo('confirm the default settings');
$I->assertEquals('rgb(75, 112, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".antragsgruen-width-main.well")[0])["border-top-left-radius"]'));

$I->wantTo('see that by default, the classic theme is activated');
$I->seeInField('#stylesheet-menuLink', '#4B7000');

$I->wantTo('see that DBJR-theme is preselected if necessary');
$I->gotoStdAdminPage()->gotoAppearance();
$I->executeJS('$(".thumbnailedLayoutSelector .layout.layout-dbjr").click();');
$I->click('.editThemeLink');
$I->seeInField('#stylesheet-menuLink', '#646464');

$I->wantTo('change the settings');
$I->fillField('#stylesheet-contentBorderRadius', '5');
$I->executeJS('$("#stylesheet-menuLink").val("#FF0000");');
$I->submitForm('.themingForm', [], 'save');

$I->assertEquals('rgb(255, 0, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('5px', $I->executeJS('return getComputedStyle($(".antragsgruen-width-main.well")[0])["border-top-left-radius"]'));

$I->wantTo('change to a regular theme again');
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->seeCheckboxIsChecked('.customThemeSelector input');
$I->executeJS('$("input[value=layout-classic").parents("label").click()');
$I->checkOption("//input[@name='siteSettings[siteLayout]'][@value='layout-classic']");
$page->saveForm();

$I->assertEquals('rgb(75, 112, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".antragsgruen-width-main.well")[0])["border-top-left-radius"]'));

$I->wantTo('reset custom theme to DBJR');
$I->click('.editThemeLink');
$I->dontSeeElementInDOM('.bootbox-prompt');
$I->executeJS('$(".btnResetTheme").click()');
$I->wait(1);
$I->seeElementInDOM('.bootbox-prompt');
$I->checkOption("//input[@name='bootbox-radio'][@value='layout-dbjr']");
$I->executeJS('$(".bootbox-accept").click()');
$I->wait(1);
$I->seeInField('#stylesheet-menuLink', '#646464');
$I->seeInField('#stylesheet-contentBorderRadius', '10');
$I->assertEquals('rgb(100, 100, 100)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".antragsgruen-width-main.well")[0])["border-top-left-radius"]'));

$I->wantTo('reset custom theme to classic');
$I->executeJS('$(".btnResetTheme").click()');
$I->wait(1);
$I->seeElementInDOM('.bootbox-prompt');
$I->checkOption("//input[@name='bootbox-radio'][@value='layout-classic']");
$I->executeJS('$(".bootbox-accept").click()');
$I->wait(1);
$I->seeInField('#stylesheet-menuLink', '#4B7000');
$I->seeInField('#stylesheet-contentBorderRadius', '10');

$I->assertEquals('rgb(75, 112, 0)', $I->executeJS('return getComputedStyle($("#motionListLink")[0])["color"]'));
$I->assertEquals('10px', $I->executeJS('return getComputedStyle($(".antragsgruen-width-main.well")[0])["border-top-left-radius"]'));
