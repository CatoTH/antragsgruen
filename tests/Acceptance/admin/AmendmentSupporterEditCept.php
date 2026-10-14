<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('add supporters to an amendment');
$I->loginAndGotoMotionList()->gotoAmendmentEdit(1);
$I->wait(1);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 0);
$I->executeJS('$(".supporterRowAdder").click()');
$I->executeJS('$(".supporterRowAdder").click()');
$I->executeJS('$(".supporterRowAdder").click()');
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 3);
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterName").val("My Name 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(0) .supporterOrga").val("My Orga 1");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1) .supporterName").val("My Name 2");');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(2) .supporterName").val("My Name 3");');
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->gotoAmendment();
$I->seeNumberOfElements('section.supporters ul li', 3);
$I->see('My Name 1', 'section.supporters ul li:nth-child(1)');
$I->see('My Orga 1', 'section.supporters ul li:nth-child(1)');
$I->see('My Name 2', 'section.supporters ul li:nth-child(2)');
$I->see('My Name 3', 'section.supporters ul li:nth-child(3)');


$I->wantTo('edit, reorder and remove supporters');
$I->gotoMotionList()->gotoAmendmentEdit(1);
$I->seeNumberOfElements('#motionSupporterHolder > ul > li', 3);
$I->seeInField('#motionSupporterHolder > ul > li:nth-child(2) .supporterName', 'My Name 2');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1)").remove()');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1)").prependTo("#motionSupporterHolder > ul")');
$I->executeJS('$("#motionSupporterHolder > ul > li:nth(1) .supporterName").val("My Name 1 changed");');
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->gotoAmendment();
$I->seeNumberOfElements('section.supporters ul li', 2);
$I->see('My Name 3', 'section.supporters ul li:nth-child(1)');
$I->see('My Name 1 changed', 'section.supporters ul li:nth-child(2)');
$I->dontSee('My Name 2', 'section.supporters');
