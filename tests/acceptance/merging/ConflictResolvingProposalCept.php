<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->wantTo('Create a proposed procedure that does not conflict, contrary to the base amendment');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->executeJS('$(".proposedChangesOpener button").click();');
$I->seeElement('#proposedChanges');
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_MODIFIED_ACCEPTED . ' input").prop("checked", true).change();');
$I->executeJS('$("#proposedChanges .saving button").click();');
$I->wait(1);
$I->click('#proposedChanges .editModification');
$I->wait(1);
$I->executeJS('$(".resetText").click()');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/et ea rebum\.<\/p>/, "et ea rebum noconflict.</p>"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->seeElement('.alert-success');


$I->wantTo('test the merging');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->wait(0.2);
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...

$I->see('noconflict', '#section_holder_2_1 ins');
$I->dontSeeElement('#paragraphWrapper_2_1 .collidingParagraph');
$I->seeElement('#paragraphWrapper_2_2 .collidingParagraph');


$I->wantTo('switch to original amendment version');

$I->executeJS('$("#paragraphWrapper_2_1 .amendmentStatus280 .dropdown-toggle").click();');
$I->wait(0.5);
$I->executeJS('$("#paragraphWrapper_2_1 .amendmentStatus280 .versionorig a").click();');
$I->wait(1);
$I->dontSee('noconflict', '#section_holder_2_1 ins');
$I->see('A big replacement', '#paragraphWrapper_2_1 .collidingParagraph ins');


$I->wantTo('test the merging with original version');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->executeJS('$(".amendment280 input[value=orig]").click();');
$I->wait(0.2);
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...

$I->dontSee('noconflict', '#section_holder_2_1 ins');
$I->seeElement('#paragraphWrapper_2_1 .collidingParagraph');
$I->seeElement('#paragraphWrapper_2_2 .collidingParagraph');


$I->wantTo('switch to proposed amendment version');

$I->executeJS('$("#paragraphWrapper_2_1 .amendmentStatus280 .dropdown-toggle").click();');
$I->wait(0.5);
$I->executeJS('$("#paragraphWrapper_2_1 .amendmentStatus280 .versionprop a").click();');
$I->wait(1);
$I->see('noconflict', '#section_holder_2_1 ins');
$I->dontSeeElement('#paragraphWrapper_2_1 .collidingParagraph');
$I->seeElement('#paragraphWrapper_2_2 .collidingParagraph');
