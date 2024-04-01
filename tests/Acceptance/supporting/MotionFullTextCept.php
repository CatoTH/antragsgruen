<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check that the field is not visible for normal users');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdUser();
$I->click('.createMotion');
$I->seeElement('.supporterData');
$I->dontSeeElementInDOM('.fullTextAdder');
$I->dontSeeElementInDOM('#supporterFullTextHolder');

$I->wantTo('check that the field is visible for admins');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->logout();
$I->loginAsStdAdmin();
$I->click('.createMotion');
$I->seeElement('.supporterData');
$I->seeElement('.fullTextAdder');
$I->dontSeeElement('#supporterFullTextHolder');
$I->click('.fullTextAdder button');
$I->seeElement('#supporterFullTextHolder');

$I->wantTo('check that the function actually works');
$I->fillField('#supporterFullTextHolder textarea', 'Tobias Hößl, KV München; Test 2');
$I->click('#supporterFullTextHolder .fullTextAdd');
$name1 = $I->executeJS('return $(".supporterRow").eq(0).find("input.name").val()');
$orga1 = $I->executeJS('return $(".supporterRow").eq(0).find("input.organization").val()');
$name2 = $I->executeJS('return $(".supporterRow").eq(1).find("input.name").val()');
if ($name1!=='Tobias Hößl' || $orga1!=='KV München' || $name2!=='Test 2') {
    $I->fail('Got invalid supporter Data: ' . $name1 . ' (' . $orga1 . ') / ' . $name2);
}
