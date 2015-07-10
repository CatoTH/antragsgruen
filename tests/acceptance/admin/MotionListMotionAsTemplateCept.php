<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test if I can create a motion using another on as a template');
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsStdAdmin();

$I->gotoStdAdminPage(true, '1laenderrat2015', '1laenderrat2015')->gotoMotionList();
$I->click('.adminMotionTable .motion8 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .motion8 .actionCol .asTemplate');
$I->see('Antrag stellen', 'h1');

$I->seeInField('#initiatorName', 'Bundesvorstand');
$I->seeElement('#resolutionDate');
$I->seeInField('#resolutionDate', '09.03.2015');
$I->seeCheckboxIsChecked('#personTypeOrga');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');


$I->gotoStdAdminPage(true, '1laenderrat2015', '1laenderrat2015')->gotoMotionList();
$I->click('.adminMotionTable .motion48 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .motion48 .actionCol .asTemplate');
$I->see('Antrag stellen', 'h1');

$I->seeInField('#initiatorName', 'Omid Nouripour');
$I->dontSeeElement('#resolutionDate');
$I->seeCheckboxIsChecked('#personTypeNatural');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$name1 = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.name").val()');
$name2 = $I->executeJS('return $(".supporterData .supporterRow").eq(1).find("input.name").val()');
if ($name1 != 'Felix Deist' || $name2 != 'Tarek Al-Wazir') {
    $I->fail('supporter data not present');
}


$I->gotoStdAdminPage(true, '1laenderrat2015', '1laenderrat2015')->gotoMotionList();
$I->click('.adminMotionTable .amendment13 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .amendment13 .actionCol .asTemplate');
$I->see('Änderungsantrag', 'h1');
$I->seeInField('#initiatorName', 'Daniel Gollasch');
$I->dontSeeElement('#resolutionDate');
$I->seeCheckboxIsChecked('#personTypeNatural');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$name1 = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.name").val()');
$name2 = $I->executeJS('return $(".supporterData .supporterRow").eq(1).find("input.name").val()');
if ($name1 != 'Antje Kapek, LV Berlin' || $name2 != 'Axel Vogel, LV Brandenburg') {
    $I->fail('supporter data not present');
}
