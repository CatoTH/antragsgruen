<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('see the activated proposed procedure');
$I->gotoConsultationHome();
$I->loginAsProposalAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->dontSeeElement('#proposedChanges');
$I->executeJS('$(".proposedChangesOpener button").click();');
$I->seeElement('#proposedChanges');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->seeElement('#proposedChanges');

$I->gotoConsultationHome();
$I->click('#proposedProcedureLink');
$I->seeElement('.motionHolder1');


$I->wantTo('deactivate proposed procedures');

$I->logout();
$I->loginAsConsultationAdmin();
$I->click('#adminLink');
$I->click('.motionType1');
$I->seeFueluxCheckboxChecked('#typeProposedProcedure');
$I->uncheckFueluxCheckbox('#typeProposedProcedure');
$I->submitForm('.adminTypeForm', [], 'save');

$I->dontSeeFueluxCheckboxChecked('#typeProposedProcedure');



$I->wantTo('confirm the proposed procedures are not visible anymore');

$I->logout();
$I->loginAsProposalAdmin();

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->dontSeeElement('#proposedChanges');
$I->dontSeeElement('.proposedChangesOpener');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->dontSeeElement('#proposedChanges');
$I->dontSeeElement('.proposedChangesOpener');

$I->gotoConsultationHome();
$I->click('#proposedProcedureLink');
$I->dontSeeElement('.motionHolder1');

