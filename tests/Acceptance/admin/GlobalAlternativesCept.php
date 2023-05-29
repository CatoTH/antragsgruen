<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoMotion();
$I->click('#sidebar .amendmentCreate');
$I->seeElementInDOM('.editorialGlobalBar input[name=globalAlternative]');

$I->wantTo('deactivate global alternatives');
$form = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->seeCheckboxIsChecked('#globalAlternatives');
$I->uncheckOption('#globalAlternatives');
$form->saveForm();
$I->dontSeeCheckboxIsChecked('#globalAlternatives');

$I->gotoMotion();
$I->click('#sidebar .amendmentCreate');
$I->dontSeeElementInDOM('.editorialGlobalBar input[name=globalAlternative]');
