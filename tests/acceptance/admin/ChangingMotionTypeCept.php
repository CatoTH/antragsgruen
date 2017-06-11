<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('create two new motion types');

$consultation = $I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Compatible motion');
$I->fillField('#typeTitlePlural', 'Compatible motions');
$I->fillField('#typeCreateTitle', 'Crete');
$I->checkOption('.preset1');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->fillField('.section33 .sectionTitle input', 'New title');
$I->fillField('.section34 .sectionTitle input', 'New motion text');
$I->submitForm('.adminTypeForm', [], 'save');

$I->click('#adminLink');
$I->click('.motionTypeCreate a');

$I->fillField('#typeTitleSingular', 'Incompatible motion');
$I->fillField('#typeTitlePlural', 'Incompatible motions');
$I->fillField('#typeCreateTitle', 'Crete');
$I->checkOption('.presetApplication');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$compatible = AcceptanceTester::FIRST_FREE_MOTION_TYPE;
$incompatible = AcceptanceTester::FIRST_FREE_MOTION_TYPE + 1;

$I->wantTo('change the type of a motion');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElement('.alert-success');
$I->wait(1);
$I->seeElementInDOM('#motionType li[data-value="' . $compatible . '"]');
$I->dontSeeElementInDOM('#motionType li[data-value="' . $incompatible . '"]');
$selected = $I->executeJS('return $("#motionType").selectlist("selectedItem").value');
$I->assertEquals(1, $selected);
$I->executeJS('$("#motionType").selectlist("selectByValue", "' . $compatible . '");');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeElement('.alert-success');

$I->click('#sidebar .view');
$I->see(strtoupper('New motion text'), 'h3');


$I->wantTo('change the type of a motion again');
$I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElement('.alert-success');
$I->wait(1);
$I->seeElementInDOM('#motionType li[data-value="' . $compatible . '"]');
$I->dontSeeElementInDOM('#motionType li[data-value="' . $incompatible . '"]');
$selected = $I->executeJS('return $("#motionType").selectlist("selectedItem").value');
$I->assertEquals($compatible, $selected);
$I->executeJS('$("#motionType").selectlist("selectByValue", "1");');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeElement('.alert-success');

$I->click('#sidebar .view');
$I->dontSee(strtoupper('New motion text'), 'h3');
$I->see(strtoupper('Antragstext'), 'h3');
