<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

// Hint: this sets Ä1 to A2 to "is proposed moval" amendment status,
// and Ä1 to A8 as the regular amendment that is proposed to be moved.
// The whole flow will maybe be easier in the future, right now it's just a workaround.

$I->wantTo('Set the status of the preplacing amendment (needs to be done first)');
$I->gotoConsultationHome();
$I->seeElement('.amendmentRow1');

// Remove relicts from previous test cases
$I->executeJS('for (let key in localStorage) localStorage.removeItem(key);');

$page = $I->loginAndGotoMotionList()->gotoAmendmentEdit(1);
$I->selectOption('#amendmentStatus', IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION);
$page->saveForm();

$page = $I->gotoConsultationHome();
$I->dontSeeElement('.amendmentRow1');

$I->wantTo('Set the status of the actual, to be moved, amendment');
$page->gotoAmendmentView(279);

$I->click('.proposedChangesOpener button');
$I->wait(0.3);
$I->seeElement('#proposedChanges');

$I->dontSeeElement('.status_' . IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION);
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION . ' input").prop("checked", true).change();');
$I->wait(0.2);
$I->seeElement('.status_' . IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION);

$I->selectOption('#movedToOtherMotion', '1');
$I->seeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .saving button").click();');

$I->wantTo('see the effects in the proposed procedure');
$I->click('#motionListLink');
$I->click('#exportProcedureBtn');
$I->click('.exportProcedureDd .linkProcedureIntern a');
$I->seeElement('.proposedProcedureOverview');
$I->see('Vorgeschlagene Verschiebung von anderem Antrag', '.amendment1');
$I->see('Verschoben zu anderem Antrag', '.amendment279');
$I->see('Oamoi a Maß und no a Maß', '.amendment1 .inserted');

$I->wantTo('see the effects in the amendment view');
$I->gotoConsultationHome()->gotoAmendmentView(279);

$I->see('Verfahrensvorschlag: Antragstext', 'h2');
$I->see('Oamoi a Maß', '#pp_section_2_0 .inserted');
$I->see('A2: O’zapft is!', '#pp_section_2_0 a'); // The link to the original motion

$I->see('A small replacement', 'ins'); // The original amendment

$I->wantTo('be able to merge the moved amendment to its motion');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSee('Ä1');
$I->click('#sidebar .mergeamendments a');
$I->seeElement('.amendment1');
$I->seeCheckboxIsChecked('#markAmendment1');
$I->checkOption('#markAmendment3');
$I->submitForm('.mergeAllRow', [], null);

$I->wait(0.5);
$I->seeElement('.toggleAmendment1.toggleActive');
$I->seeElement('.toggleAmendment3.toggleActive');

$I->see('Oamoi a Maß', '.ice-ins');
$I->see('Woibbadinga', '.ice-del');
