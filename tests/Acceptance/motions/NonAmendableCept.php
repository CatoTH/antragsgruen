<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure it is amendable in the standard version');
$I->gotoConsultationHome();
$I->gotoMotion();
$I->seeElement('#sidebar .amendmentCreate');
$I->dontSeeElement('#sidebar .amendmentCreate .onlyAdmins');

$I->wantTo('change the nonAmendable-setting');
$I->loginAsStdAdmin();
$form = $I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeCheckboxIsChecked('#nonAmendable');
$I->checkOption('#nonAmendable');
$form->saveForm();

$I->wantTo('still be able to amend it as an admin');
$I->gotoMotion();
$I->seeElement('#sidebar .amendmentCreate');
$I->seeElement('#sidebar .amendmentCreate .onlyAdmins');

$I->wantTo('not be able to amend it as a regular user');
$I->logout();
$I->gotoMotion();
$I->dontSeeElement('#sidebar .amendmentCreate');
$I->dontSeeElement('#sidebar .amendmentCreate .onlyAdmins');
