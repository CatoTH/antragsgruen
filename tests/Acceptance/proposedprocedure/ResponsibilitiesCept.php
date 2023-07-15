<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate responsibilities');
$I->loginAndGotoMotionList();
$I->dontSeeElement('.responsibilityCol');
$I->dontSeeElement('.activateResponsibilities');
$I->click('#activateFncBtn');
$I->seeElement('.activateResponsibilities');
$I->click('.activateResponsibilities');
$I->seeElement('.responsibilityCol');
$I->seeElement('.alert-success');
$I->dontSeeElement('.filterResponsibility');

$I->wantTo('set a responsibility for a motion');
$I->dontSeeElement('.motion3 .responsibilityCol .dropdown-menu');
$I->click('.motion3 .responsibilityCol .respButton');
$I->seeElement('.motion3 .responsibilityCol .dropdown-menu');
$I->seeElement('.motion3 .responsibilityCol .respUserNone.selected');
$I->click('.motion3 .responsibilityCol .respUser8');
$I->wait(0.5);
$I->dontSeeElement('.motion3 .responsibilityCol .dropdown-menu');
$I->see('Proposal Admin', '.motion3 .responsibilityUser');

$I->click('.motion3 .responsibilityCol .respButton');
$I->seeElement('.motion3 .responsibilityCol .dropdown-menu');
$I->seeElement('.motion3 .responsibilityCol .respUser8.selected');
$I->fillField('#respCommmotion3', 'who else?');
$I->click('.motion3 .responsibilityCol .respCommentRow button');
$I->wait(0.5);
$I->dontSeeElement('.motion3 .responsibilityCol .dropdown-menu');
$I->see('Proposal Admin', '.motion3 .responsibilityUser');
$I->see('who else?', '.motion3 .responsibilityComment');

$I->wantTo('set a responsibility for an amendment');
$I->dontSeeElement('.amendment3 .responsibilityCol .dropdown-menu');
$I->click('.amendment3 .responsibilityCol .respButton');
$I->seeElement('.amendment3 .responsibilityCol .dropdown-menu');
$I->seeElement('.amendment3 .responsibilityCol .respUserNone.selected');
$I->click('.amendment3 .responsibilityCol .respUser7');
$I->wait(0.5);
$I->dontSeeElement('.amendment3 .responsibilityCol .dropdown-menu');
$I->see('Single-Consultation Admin', '.amendment3 .responsibilityUser');

$I->click('.amendment3 .responsibilityCol .respButton');
$I->seeElement('.amendment3 .responsibilityCol .dropdown-menu');
$I->seeElement('.amendment3 .responsibilityCol .respUser7.selected');
$I->fillField('#respCommamendment3', 'It\'s your turn');
$I->click('.amendment3 .responsibilityCol .respCommentRow button');
$I->wait(0.5);
$I->dontSeeElement('.amendment3 .responsibilityCol .dropdown-menu');
$I->see('Single-Consultation Admin', '.amendment3 .responsibilityUser');
$I->see('It\'s your turn', '.amendment3 .responsibilityComment');

$I->wantTo('see it in the proposed procedure');
$I->click('#exportProcedureBtn');
$I->click('.exportProcedureDd .linkProcedureIntern a');
$I->seeElement('.proposedProcedureOverview');
$I->see('Proposal Admin', '.motion3 .responsibilityCol');
$I->see('who else?', '.motion3 .responsibilityCol');

$I->wantTo('reset it to zero');
$I->click('.motion3 .responsibilityCol .respButton');
$I->fillField('#respCommmotion3', '');
$I->click('.motion3 .responsibilityCol .respCommentRow button');
$I->wait(0.5);
$I->click('.motion3 .responsibilityCol .respButton');
$I->click('.motion3 .responsibilityCol .respUserNone');
$I->wait(0.5);
$I->dontSeeElement('.motion3 .responsibilityCol .dropdown-menu');

$user = $I->executeJS('return $(".motion3 .responsibilityUser").text()');
$I->assertEquals('', $user);
$comment = $I->executeJS('return $(".motion3 .responsibilityComment").text()');
$I->assertEquals('', $comment);

$I->wantTo('check the filter');
$I->click('#motionListLink');
$I->seeElement('.filterResponsibility');
$I->seeElement('.motion2');
$I->seeElement('.amendment3');
$I->selectOption('.filterResponsibility select', '7');
$I->submitForm('.motionListSearchForm', [], '');
$I->dontSeeElement('.motion2');
$I->seeElement('.amendment3');
