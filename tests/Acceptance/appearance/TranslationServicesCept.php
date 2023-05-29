<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Not see the translation widget');
$I->gotoConsultationHome();
$I->dontSeeElement('.translateWidget');
$I->gotoMotion();
$I->dontSeeElement('.translateWidget');
$I->gotoAmendment();
$I->dontSeeElement('.translateWidget');


$I->wantTo('Activate the translation widget');
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('#appearanceLink');
$I->dontSeeCheckboxIsChecked('#translationService');
$I->dontSeeElement('.translationService .services');
$I->executeJS('$("#translationService").prop("checked", true).trigger("change");');
$I->checkOption("//input[@name='translationSpecificService'][@value='bing']");
$I->submitForm('#consultationAppearanceForm', [], 'save');


$I->wantTo('See the translation widget');
$I->gotoConsultationHome();
$I->seeElement('.translateWidget');
$I->gotoMotion();
$I->seeElement('.translateWidget');
$I->gotoAmendment();
$I->seeElement('.translateWidget');

$I->dontSeeElement('.dropdown-menu');
$I->executeJS('$("#translatePageBtn").trigger("click")');
$I->seeElement('.dropdown-menu');
$I->see('Español', '.dropdown-menu');
