<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test if I can create an amendment using another one as template');


$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsStdAdmin();

$I->gotoMotionList();
$I->click('.adminMotionTable .amendment13 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .amendment13 .actionCol .asTemplate');
$I->see('Änderungsantrag', 'h1');
$I->seeInField('#initiatorPrimaryName', 'Robin Stapf');
$I->dontSeeElement('#resolutionDate');
$I->seeCheckboxIsChecked('#personTypeNatural');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$name1 = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.name").val()');
$name2 = $I->executeJS('return $(".supporterData .supporterRow").eq(1).find("input.name").val()');
if ($name1 !== 'Lena Vaatz, LV Rack' || $name2 !== 'Wolfram Ruth, LV Brandenburg') {
    $I->fail('supporter data not present');
}


$I->gotoConsultationHome();
$I->gotoMotionList();

$I->click('.adminMotionTable .amendment1 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .amendment1 .actionCol .asTemplate');
$I->see('Änderungsantrag zu A2: O’zapft is! stellen', 'h1');

$I->seeInField('#initiatorPrimaryName', 'Tester');
$I->see('Oamoi a Maß', '.ice-ins');

$I->executeJS('$("#section_holder_2 .resetText").click();');
$I->dontSee('Oamoi a Maß', '.ice-ins');
