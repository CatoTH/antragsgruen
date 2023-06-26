<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check if editorial amendments are allowed');
$I->gotoMotion();
$I->click('#sidebar .amendmentCreate a');
$I->seeElement('.editorialChange');


$I->wantTo('disable editorial amendments');
$form = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->seeCheckboxIsChecked('#editorialAmendments');
$I->uncheckOption('#editorialAmendments');
$form->saveForm();
$I->dontSeeCheckboxIsChecked('#editorialAmendments');

$I->gotoMotion();
$I->logout();
$I->click('#sidebar .amendmentCreate a');
$I->dontSeeElement('.editorialChange');
