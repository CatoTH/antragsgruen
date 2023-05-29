<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test if I can create a motion using another one as template');
$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');
$I->loginAsStdAdmin();

$I->gotoMotionList();
$I->click('.adminMotionTable .motion8 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .motion8 .actionCol .asTemplate');
$I->see('Antrag stellen', 'h1');

$I->seeInField('#initiatorPrimaryName', 'Bundesvorstand');
$I->seeElement('#resolutionDate');
$I->seeInField('#resolutionDate', '09.03.2015');
$I->seeCheckboxIsChecked('#personTypeOrga');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');


$I->gotoMotionList();
$I->click('.adminMotionTable .motion48 .actionCol .dropdown-toggle');
$I->click('.adminMotionTable .motion48 .actionCol .asTemplate');
$I->see('Antrag stellen', 'h1');

$I->seeInField('#initiatorPrimaryName', 'Axel Wolbring');
$I->dontSeeElement('#resolutionDate');
$I->seeCheckboxIsChecked('#personTypeNatural');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$name1 = $I->executeJS('return $(".supporterData .supporterRow").eq(0).find("input.name").val()');
$name2 = $I->executeJS('return $(".supporterData .supporterRow").eq(1).find("input.name").val()');
if ($name1 !== 'Wilma Daßler' || $name2 !== 'Oliver Ende') {
    $I->fail('supporter data not present');
}
