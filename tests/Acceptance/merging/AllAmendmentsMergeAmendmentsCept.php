<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSeeElement('.sidebarActions .mergeamendments');

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->wait(0.5);
$I->executeJS('$(".selectAll").click()');

$I->see('Einpflegen beginnen');
$I->click('.mergeAllRow .btn-primary');
$I->wait(0.5);
$I->see('annehmen oder ablehnen');
$I->see('Neuer Punkt', '.ice-ins');
$I->see('Oamoi a Maß', '.ice-ins');
$I->see('Woibbadinga', '#sections_2_4_wysiwyg .ice-ins strong'); // Ä3

$I->see('Woibbadinga damischa', '#sections_4_0_wysiwyg .ice-del');
$I->see('Schooe', '#sections_4_0_wysiwyg .ice-ins');

$I->see('Kollidierender Änderungsantrag', '#paragraphWrapper_2_4 .collisionsHolder');
$I->see('Woibbadinga noch da Giasinga', '#paragraphWrapper_2_4 .collidingParagraph .ice-del'); // Ä2
$I->seeElement('#paragraphWrapper_2_4 .toggleAmendment3.toggleActive'); // Ä2
$I->seeElement('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive'); // Ä3
$I->dontSee('Woibbadinga noch da Giasinga', '#section_holder_2_4 .ice-del');

$cid0 = $I->executeJS('return $("[data-cid=0]").length;');
$cid1 = $I->executeJS('return $("[data-cid=1]").length;');
$cid2 = $I->executeJS('return $("[data-cid=2]").length;');
$cid3 = $I->executeJS('return $("[data-cid=3]").length;');
if ($cid0 != 9 || $cid1 != 7 || $cid2 != 3 || $cid3 != 2) {
    $I->fail('wrong number of cid\'s: ' . $cid0 . ' / ' . $cid1 . ' / ' . $cid2 . ' / ' . $cid3);
}

// "Neuer Punkt": reject
$I->executeJS('$("#sections_2_1_wysiwyg [data-cid=2] .appendHint").trigger("mouseover"); $("button.reject").click();');
// "Woibbadinga damischa raus, Schooe rein": accept
$I->executeJS('$("#sections_4_0_wysiwyg [data-cid=1].appendHint").first().trigger("mouseover"); $("button.accept").click();');
// Deactivate Ä3 from the colliding Paragraph
$I->executeJS('$("#paragraphWrapper_2_4 .toggleAmendment270").click();');

$I->wait(1);

// Now that Ä3 is gone, Ä2 should be embedded in the text
$I->dontSee('Woibbadinga', '#sections_2_4_wysiwyg .ice-ins strong'); // Ä3
$I->see('Woibbadinga noch da Giasinga', '#sections_2_4_wysiwyg .ice-del'); // Ä2

$I->dontSee('Kollidierender Änderungsantrag', '#paragraphWrapper_2_4 .collisionsHolder');
$I->dontSee('Woibbadinga noch da Giasinga', '#paragraphWrapper_2_4 .collidingParagraph');
$I->seeElement('#paragraphWrapper_2_4 .toggleAmendment270.btn-default');
$I->dontSeeElement('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive');

$I->wantTo('make some changes by hand');

$I->dontSeeElement('#paragraphWrapper_2_7 .changedIndicator');
$I->executeJS('CKEDITOR.instances.sections_2_7_wysiwyg.setData(CKEDITOR.instances.sections_2_7_wysiwyg.getData().replace(/Ende<\/ins>\./gi, "Ende</ins>. With an hand-written appendix."));');
$I->wait(0.5);
$I->seeElement('#paragraphWrapper_2_7 .changedIndicator');
$I->see('With an hand-written appendix.', '#paragraphWrapper_2_7');

$I->wantTo('accept all changes in another paragraph');

$I->see('Leonhardifahrt ma da middn', '#paragraphWrapper_4_2 .ice-del');
$I->executeJS('$("#paragraphWrapper_4_2").find(".acceptAll").click();');


$I->wantTo('Set the status of an amendment');
$I->dontSeeElement('#paragraphWrapper_2_4 .amendmentStatus3 .dropdown-menu');
$I->executeJS('$("#paragraphWrapper_2_4 .amendmentStatus3 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_4 .amendmentStatus3 .dropdown-menu');
$I->fillField('#votesComment2_4_3', 'Accepted by a small margin');
$I->fillField('#votesYes2_4_3', '12');
$I->fillField('#votesNo2_4_3', '10');
$I->fillField('#votesInvalid2_4_3', '1');

$I->dontSeeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->executeJS('$("#paragraphWrapper_2_7 .amendmentStatus3 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->seeInField('#votesComment2_7_3', 'Accepted by a small margin');
$I->seeInField('#votesYes2_7_3', '12');
$I->seeInField('#votesNo2_7_3', '10');
$I->seeInField('#votesInvalid2_7_3', '1');

$I->executeJS('$("#paragraphWrapper_2_7 .amendmentStatus274 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status3.selected');
$I->click('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status5 a'); // Setting to "declined". Is an error and will be corrected below


$I->wantTo('Save the changes');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->dontSee('Leonhardifahrt ma da middn', '#paragraphWrapper_4_2');


$I->submitForm('.motionMergeForm', [], 'save');

$I->see('Überarbeitung kontrollieren', 'h1');
$I->see('Oamoi a Maß');
$I->dontSee('Neuer Punkt');
$I->see('Alternatives Ende');


$I->wantTo('change some amendment statuses');

$I->seeInField('#votesComment3', 'Accepted by a small margin');
$I->seeInField('#votesYes3', '12');
$I->seeInField('#votesNo3', '10');
$I->seeInField('#votesInvalid3', '1');

$I->fillField('#votesComment3', 'Accepted by a great margin');
$I->fillField('#votesYes3', '15');
$I->fillField('#votesNo3', '4');

$I->seeOptionIsSelected('#amendmentStatus274', 'Abgelehnt');
$I->selectOption('#amendmentStatus274', IMotion::STATUS_ACCEPTED);


$I->wantTo('modify the text');

$I->submitForm('#motionConfirmForm', [], 'modify');


$I->dontSee('Kollidierender Änderungsantrag', '#paragraphWrapper_2_4 .collisionsHolder');
$I->dontSee('Woibbadinga noch da Giasinga', '#paragraphWrapper_2_4 .collidingParagraph');
$I->see('Woibbadinga noch da Giasinga', '#sections_2_4_wysiwyg .ice-del'); // Ä2
$I->seeElement('#paragraphWrapper_2_4 .toggleAmendment270.btn-default');
$I->dontSeeElement('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive');
$I->see('With an hand-written appendix.', '#paragraphWrapper_2_7');

$I->see('Oamoi a Maß und no a Maß', '#paragraphWrapper_2_4 .ice-ins');


$I->dontSeeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->executeJS('$("#paragraphWrapper_2_7 .amendmentStatus3 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->seeInField('#votesComment2_7_3', 'Accepted by a great margin');
$I->seeInField('#votesYes2_7_3', '15');
$I->seeInField('#votesNo2_7_3', '4');
$I->seeInField('#votesInvalid2_7_3', '1');

$I->executeJS('$("#paragraphWrapper_2_7 .amendmentStatus274 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status4.selected');


$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->submitForm('.motionMergeForm', [], 'save');


$I->wantTo('add a voting result');

$I->dontSeeElement('.contentVotingResult');
$I->dontSeeElement('.contentVotingResultComment');
$I->click('.votingResultOpener');
$I->seeElement('.contentVotingResult');
$I->seeElement('.contentVotingResultComment');
$I->fillField('#votesYes', '15');
$I->fillField('#votesNo', '5');
$I->fillField('#votesAbstention', '2');
$I->fillField('#votesInvalid', '0');
$I->fillField('#votesComment', 'Accepted by mayority');


$I->seeInField('#votesComment3', 'Accepted by a great margin');
$I->seeInField('#votesYes3', '15');
$I->seeInField('#votesNo3', '4');
$I->seeInField('#votesInvalid3', '1');


$I->wantTo('submit the new form');

$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('check if the modifications were made');
$I->see('Beschluss', '.statusRow');
$I->see('Version 2', '.motionDataTable .historyOpener .currVersion');
$I->see('Oamoi a Maß');
$I->see('Schooe');
$I->see('With an hand-written appendix.');
$I->dontSee('Neuer Punkt');
$I->see('Alternatives Ende');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->see('Version 1', '.motionDataTable .motionHistory a.motion2');
$I->dontSee('Leonhardifahrt ma da middn');

$I->see('Accepted by mayority', '.votingResultRow');
$I->see('Ja: 15, Nein: 5, Enthaltungen: 2', '.votingResultRow');

$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->click('.motionDataTable .motionHistory a.motion2');
$I->see('Achtung: dies ist eine alte Fassung', '.motionReplacedBy.alert-danger');
$I->seeElement('.bookmarks .amendment276');
$I->seeElement('.bookmarks .amendment3');

$I->click('.amendment3 a');
$I->see('Accepted by a great margin', '.votingResultRow');
$I->see('Ja: 15, Nein: 4, Ungültig: 1', '.votingResultRow');

$I->gotoConsultationHome();
$I->click('.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->click('.motionDataTable .motionHistory a.motion2');
$I->click('.amendment274 a');
$I->see('Angenommen', '.statusRow');
