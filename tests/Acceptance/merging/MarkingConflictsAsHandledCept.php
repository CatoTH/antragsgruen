<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoMotion();

$I->wantTo('hide the conflict and save the draft');

$I->click('#sidebar .mergeamendments a');
$I->dontSeeElementInDOM('.draftExistsAlert');
$I->wait(0.2);
$I->executeJS('$(".toMergeAmendments .selectAll").trigger("click");');
$I->click('.mergeAllRow .btn-primary');
$I->see('Woibbadinga noch da Giasinga', '#paragraphWrapper_2_4 .collidingParagraph3');

$I->executeJS('$("#draftSavingPanel").hide();'); // Workaround for non-clickable error
$I->click('#paragraphWrapper_2_4 .collidingParagraph3 .hideCollision');
$I->executeJS('$("#draftSavingPanel").show();'); // Workaround for non-clickable error

$I->click('#draftSavingPanel .saveDraft');
$I->executeJS('$(window).unbind("beforeunload");'); // Prevent the alert from disturbing the window

$I->wait(1);
$I->dontSeeElementInDOM('#paragraphWrapper_2_4 .collidingParagraph3');

$I->wantTo('show the conflict again and save the draft');

$I->gotoMotion();
$I->click('#sidebar .mergeamendments a');
$I->click('.draftExistsAlert .btn');
$I->executeJS('$("#draftSavingPanel").hide();'); // Workaround for non-clickable error
$I->dontSeeElementInDOM('#paragraphWrapper_2_4 .collidingParagraph3');
$I->click('#paragraphWrapper_2_4 .amendmentStatus3 .toggleAmendment');
$I->wait(0.3);
$I->click('#paragraphWrapper_2_4 .amendmentStatus3 .toggleAmendment');
$I->wait(0.3);
$I->seeElement('#paragraphWrapper_2_4 .collidingParagraph3');
$I->executeJS('$("#draftSavingPanel").show();'); // Workaround for non-clickable error

$I->click('#draftSavingPanel .saveDraft');
$I->executeJS('$(window).unbind("beforeunload");'); // Prevent the alert from disturbing the window

$I->gotoMotion();
$I->click('#sidebar .mergeamendments a');
$I->click('.draftExistsAlert .btn');
$I->seeElement('#paragraphWrapper_2_4 .collidingParagraph3');
