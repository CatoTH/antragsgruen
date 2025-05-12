<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('create two new motion types');

$consultation = $I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Compatible motion');
$I->fillField('#typeTitlePlural', 'Compatible motions');
$I->fillField('#typeCreateTitle', 'Create');
$I->checkOption('.preset1');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->fillField('.section' . AcceptanceTester::FIRST_FREE_MOTION_SECTION . ' .sectionTitle input', 'New title');
$I->fillField('.section' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1 ) . ' .sectionTitle input', 'New motion text');
$I->submitForm('.adminTypeForm', [], 'save');

$I->click('#adminLink');
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Incompatible motion');
$I->fillField('#typeTitlePlural', 'Incompatible motions');
$I->fillField('#typeCreateTitle', 'Create');
$I->checkOption('.presetApplication');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$compatible = AcceptanceTester::FIRST_FREE_MOTION_TYPE;
$incompatible = AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1;

$I->wantTo('change the type of a motion');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElement('.alert-success');
$I->wait(1);
$I->seeElementInDOM('#motionType option[value="' . $compatible . '"]');
$I->dontSeeElementInDOM('#motionType option[value="' . $incompatible . '"]');
$selected = $I->executeJS('return $("#motionType").val()');
$I->assertEquals(1, $selected);
$I->executeJS('$("#motionType").val("' . $compatible . '");');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeElement('.alert-success');

$I->click('#sidebar .view');
$I->see(strtoupper('New motion text'), 'h2');


$I->wantTo('change the type of a motion again');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElement('.alert-success');
$I->wait(1);
$I->seeElementInDOM('#motionType option[value="' . $compatible . '"]');
$I->dontSeeElementInDOM('#motionType option[value="' . $incompatible . '"]');
$selected = $I->executeJS('return $("#motionType").val()');
$I->assertEquals($compatible, $selected);
$I->executeJS('$("#motionType").val("1");');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeElement('.alert-success');

$I->click('#sidebar .view');
$I->dontSee(strtoupper('New motion text'), 'h2');
$I->see(strtoupper('Antragstext'), 'h2');
