<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test numbered lists');

$I->gotoConsultationHome();
$I->gotoMotion(true, 3);
$I->see('Zeilenumbruch unterstrichen');

$number = $I->executeJS('return $("#section_2_2 .text ol").attr("start")');
if ($number != 1) {
    $I->fail('First list item is not 1');
}

$number = $I->executeJS('return $("#section_2_3 .text ol").attr("start")');
if ($number != 2) {
    $I->fail('First list item is not 1');
}
