<?php

/** @var \Codeception\Scenario $scenario */
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
$I->see('Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.', '.ice-del');

$I->see('Woibbadinga damischa', '#sections_4_0_wysiwyg .ice-del');
$I->see('Schooe', '#sections_4_0_wysiwyg .ice-ins');

$I->see('Kollidierender Änderungsantrag', '#paragraphWrapper_2_7 .collisionsHolder');
$I->see('Alternatives Ende', '#paragraphWrapper_2_7 .collidingParagraph');
$I->seeElement('#paragraphWrapper_2_7 .toggleAmendment3.toggleActive');
$I->dontSee('Alternatives Ende', '#section_holder_2_7');

$cid0 = $I->executeJS('return $("[data-cid=0]").length;');
$cid1 = $I->executeJS('return $("[data-cid=1]").length;');
$cid2 = $I->executeJS('return $("[data-cid=2]").length;');
$cid3 = $I->executeJS('return $("[data-cid=3]").length;');
if ($cid0 != 9 || $cid1 != 6 || $cid2 != 4 || $cid3 != 1) {
    $I->fail('wrong number of cid\'s: ' . $cid0 . ' / ' . $cid1 . ' / ' . $cid2 . ' / ' . $cid3);
}

// "Neuer Punkt": reject
$I->executeJS('$("#sections_2_1_wysiwyg [data-cid=2] .appendHint").trigger("mouseover"); $("button.reject").click();');
// "Woibbadinga damischa raus, Schooe rein": accept
$I->executeJS('$("#sections_4_0_wysiwyg [data-cid=1].appendHint").first().trigger("mouseover"); $("button.accept").click();');
// Deactivate Ä2 from the colliding Paragraph
$I->executeJS('$("#paragraphWrapper_2_7 .toggleAmendment3").click();');

$I->wait(1);

$I->dontSee('Neuer Punkt', '.ice-ins');
$I->dontSee('Neuer Punkt');
$I->see('Oamoi a Maß');

$I->dontSee('Woibbadinga damischa', '#sections_4_0_wysiwyg .ice-del');
$I->dontSee('Schooe', '#sections_4_0_wysiwyg .ice-ins');
$I->see('Schooe', '#sections_4_0_wysiwyg');

// Now that Ä2 is gone, Ä6 should be embedded in the text
$I->dontSee('Kollidierender Änderungsantrag', '#paragraphWrapper_2_7 .collisionsHolder');
$I->dontSee('Alternatives Ende', '#paragraphWrapper_2_7 .collidingParagraph');
$I->see('Alternatives Ende', '#section_holder_2_7');
$I->seeElement('#paragraphWrapper_2_7 .toggleAmendment3.btn-default');
$I->dontSeeElement('#paragraphWrapper_2_7 .toggleAmendment3.toggleActive');

$I->wantTo('make some changes by hand');

$I->dontSeeElement('#paragraphWrapper_2_7 .changedIndicator');
$I->executeJS('CKEDITOR.instances.sections_2_7_wysiwyg.setData(CKEDITOR.instances.sections_2_7_wysiwyg.getData().replace(/Ende<\/ins>\./gi, "Ende</ins>. With an hand-written appendix."));');
$I->wait(0.5);
$I->seeElement('#paragraphWrapper_2_7 .changedIndicator');
$I->see('With an hand-written appendix.', '#paragraphWrapper_2_7');

$I->wantTo('accept all changes in another paragraph');

$I->see('mechad mim Spuiratz', '#paragraphWrapper_2_4 .ice-del');
$I->see('Oamoi a Maß und no a Maß', '#paragraphWrapper_2_4 .ice-ins');
$I->executeJS('$("#paragraphWrapper_2_4").find(".acceptAll").click();');


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

$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu .status3.selected');
$I->click('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu .status5 a'); // Setting to "declined". Is a nerror and will be corrected below


$I->wantTo('Save the changes');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->dontSee('mechad mim Spuiratz', '#paragraphWrapper_2_4');
$I->see('Oamoi a Maß und no a Maß', '#paragraphWrapper_2_4');
$I->dontSee('Oamoi a Maß und no a Maß', '#paragraphWrapper_2_4 .ice-ins');


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

$I->seeFueluxOptionIsSelected('#amendmentStatus3', 5);
$I->selectFueluxOption('#amendmentStatus3', 4); // Correcting status to "Accepted"


$I->wantTo('modify the text');

$I->submitForm('#motionConfirmForm', [], 'modify');

$I->dontSee('Kollidierender Änderungsantrag', '#paragraphWrapper_2_7 .collisionsHolder');
$I->dontSee('Alternatives Ende', '#paragraphWrapper_2_7 .collidingParagraph');
$I->see('Alternatives Ende', '#section_holder_2_7');
$I->seeElement('#paragraphWrapper_2_7 .toggleAmendment3.btn-default');
$I->dontSeeElement('#paragraphWrapper_2_7 .toggleAmendment3.toggleActive');
$I->see('With an hand-written appendix.', '#paragraphWrapper_2_7');

$I->dontSee('mechad mim Spuiratz', '#paragraphWrapper_2_4');
$I->see('Oamoi a Maß und no a Maß', '#paragraphWrapper_2_4');


$I->dontSeeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->executeJS('$("#paragraphWrapper_2_7 .amendmentStatus3 button.dropdown-toggle").click()');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu');
$I->seeInField('#votesComment2_7_3', 'Accepted by a great margin');
$I->seeInField('#votesYes2_7_3', '15');
$I->seeInField('#votesNo2_7_3', '4');
$I->seeInField('#votesInvalid2_7_3', '1');
$I->seeElement('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu .status4.selected');


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
$I->see('A2neu', 'h1');
$I->see('Oamoi a Maß');
$I->see('Schooe');
$I->see('With an hand-written appendix.');
$I->dontSee('Neuer Punkt');
$I->see('Alternatives Ende');
$I->see('A2:', '.replacesMotion');
$I->dontSee('mechad mim Spuiratz');
$I->see('Oamoi a Maß und no a Maß');

$I->see('Accepted by mayority', '.votingResultRow');
$I->see('Ja: 15, Nein: 5, Enthaltungen: 2', '.votingResultRow');

$I->click('.replacesMotion a');
$I->see('Achtung: dies ist eine alte Fassung', '.motionReplayedBy.alert-danger');
$I->seeElement('.bookmarks .amendment276');
$I->seeElement('.bookmarks .amendment3');

$I->click('.amendment3 a');
$I->see('Accepted by a great margin', '.votingResultRow');
$I->see('Ja: 15, Nein: 4, Ungültig: 1', '.votingResultRow');
$I->see('Angenommen', '.statusRow');
