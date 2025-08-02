<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->initializeAndGoHome();

$I->loginAsProposalAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->wantTo('propose a obsoleted-by status');
$I->clickJS('.proposedChangesOpener button');
$I->seeElement('#proposedChanges');
$I->dontSeeElement('#proposedChanges #statusCustomStr');
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_CUSTOM_STRING . ' input").prop("checked", true).change();');
$I->seeElement('#proposedChanges #statusCustomStr');
$I->seeInField('#proposedChanges input[name=newVersion]', 'current');
$I->fillField('#proposedChanges #statusCustomStr', 'Under review');
$I->seeElement('#proposedChanges .saving');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);

$I->wantTo('notify the initiator of the motion');
$I->clickJS('#proposedChanges button.notifyProposer');
$I->wait(0.5);
$I->seeElement('.notifyProposerSection');
$I->clickJS('#proposedChanges button[name=notificationSubmit]');
$I->wait(1);
$I->see('Der/die Antragsteller*in wurde am');

$I->wantTo('reject the proposal as the user');
$I->logout();
$I->loginAsStdUser();
$I->submitForm('.agreeToProposal', [], 'setProposalDisagree');
$I->seeElement('.alert-success');

$I->wantTo('comment as admin, no new version created');
$I->logout();
$I->loginAsProposalAdmin();
$I->fillField('#proposedChanges .proposalCommentForm textarea', 'Internal comment!');
$I->clickJS('#proposedChanges .proposalCommentForm button');
$I->wait(1);
$I->see('Internal comment!', '#proposedChanges .proposalCommentForm .commentList');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeInField('#proposedChanges #statusCustomStr', 'Under review');
// Check that no new version was created

$I->wantTo('create a new version');

$I->seeInField('#proposedChanges input[name=proposalStatus]', (string)IMotion::STATUS_CUSTOM_STRING);
$I->seeElement('#proposedChanges .status_' . IMotion::STATUS_CUSTOM_STRING);
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_ACCEPTED . ' input").prop("checked", true).change();');
$I->seeInField('#proposedChanges input[name=newVersion]', 'new');
$I->executeJS('$("#proposedChanges input[name=newVersion]").change();');
$I->seeElement('#proposedChanges .saving');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);

$I->wantTo('see the old version');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeInField('#proposedChanges input[name=proposalStatus]', (string)IMotion::STATUS_ACCEPTED);
$I->seeElement('#proposedChanges .proposalHistory');
$I->click('#proposedChanges a.version1');
$I->wait(1);
$I->seeElement('#proposedChanges .status_' . IMotion::STATUS_CUSTOM_STRING);
$I->seeInField('#proposedChanges input[name=proposalStatus]', (string)IMotion::STATUS_CUSTOM_STRING);
$I->seeElement('#proposedChanges .notificationStatus .rejected');
// @TODO check old version, not changable

$I->wantTo('only see version 1 as user');
$I->logout();
$I->loginAsStdUser();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->see('Under review', '.agreeToProposal');
$I->seeElement('.agreeToProposal .agreement .disagreed');
$I->seeElement('.agreeToProposal .updateDecision');
