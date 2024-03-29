<?php

/** @var \Codeception\Scenario $scenario */

use Tests\_pages\MotionPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('edit a motion');
$I->loginAndGotoMotionList('supporter', 'supporter')->gotoMotionEdit(116);
$I->wait(1);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 0);
$I->executeJS('$(".supporterRowAdder").click()');
$I->executeJS('$(".supporterRowAdder").click()');
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 2);
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterName").val("My Name 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterOrga").val("My Orga 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1) .supporterName").val("My Name 2");');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoStdAdminPage('supporter', 'supporter')->gotoMotionTypes(10);
$I->checkOption('#typeHasOrga');
$I->submitForm('.adminTypeForm', [], 'save');

$I->logout();


$I->wantTo('support the motion as a regular user');

$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$I->loginAsStdUser();
$I->fillField('input[name=motionSupportName]', 'My login-name');
$I->fillField('input[name=motionSupportOrga]', 'My login-organisation');
$I->submitForm('.motionSupportForm', [], 'motionSupport');

$I->seeNumberOfElements('section.supporters ul li', 3);
$thirdName = $I->executeJS('return $("section.supporters ul li:nth(2)").text()');
$I->assertStringContainsString('My login-name', $thirdName);

$I->logout();


$I->wantTo('edit the motion again');
$I->loginAndGotoMotionList('supporter', 'supporter')->gotoMotionEdit(116);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 3);
$I->seeInField('#motionSupporterHolder > ul > li .supporterName', 'My login-name');
$I->see('testuser@example.org', '#motionSupporterHolder > ul > li');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(2)").prependTo("#motionSupporterHolder > ul")');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterName").val("My login-name 2");');


$I->clickJS('.initiatorData .moreInitiatorsAdder .adderBtn');
$I->clickJS('.initiatorData .moreInitiatorsAdder .adderBtn');
$lineNumbers = $I->executeJS('
    $(".initiatorData .initiatorRow").eq(1).remove();
    $(".initiatorData .initiatorRow:nth(0) .name").val("Initiator 2");
    $(".initiatorData .initiatorRow:nth(0) .organization").val("Organization 2");
    return $(".initiatorData .initiatorRow").length;
');

if ($lineNumbers != 1) {
    $I->fail('an invalid number of initiator rows: ' . $lineNumbers . ' (should be: 1)');
}

$I->submitForm('#motionUpdateForm', [], 'save');



$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);
$firstName = $I->executeJS('return $("section.supporters ul li:nth(0)").text()');
$I->assertStringContainsString('My login-name 2', $firstName);
$I->see('Initiator 2 (Organization 2)', '.motionDataTable');





$I->wantTo('add some suporters using full-text');

$I->gotoMotionList()->gotoMotionEdit(116);

$I->dontSeeElement('#supporterFullTextHolder');
$I->click('.fullTextAdder button');
$I->seeElement('#supporterFullTextHolder');

$I->fillField('#supporterFullTextHolder textarea', 'Yet another name, KV München; Another Name 3');
$I->click('#supporterFullTextHolder .fullTextAdd');

$I->submitForm('#motionUpdateForm', [], 'save');


$I->openPage(MotionPage::class, [
    'subdomain'        => 'supporter',
    'consultationPath' => 'supporter',
    'motionSlug'       => 116,
]);

$fifthName = $I->executeJS('return $("section.supporters ul li:nth(4)").text()');
$I->assertStringContainsString('Another Name 3', $fifthName);
$fourthName = $I->executeJS('return $("section.supporters ul li:nth(3)").text()');
$I->assertStringContainsString('KV München', $fourthName);
