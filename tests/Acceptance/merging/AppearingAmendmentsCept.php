<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

// Make Ä6 and Ä7 invisible
$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 274, IMotion::STATUS_SUBMITTED_UNSCREENED);
$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 276, IMotion::STATUS_SUBMITTED_UNSCREENED);

$I->gotoConsultationHome()->gotoMotionView(2);
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->wait(0.5);

$I->seeElement('.amendment272');
$I->dontSeeElement('.amendment274');
$I->dontSeeElement('.amendment276');
$I->executeJS('$(".selectAll").click()');
$I->wait(0.3);
$I->click('.mergeAllRow .btn-primary');

$I->wait(0.5);
$I->seeElement('.toggleAmendment3');
$I->seeElement('.toggleAmendment272');
$I->dontSeeElement('.toggleAmendment274');
$I->dontSeeElement('.toggleAmendment276');
$I->dontSeeElement('#newAmendmentAlert');

$I->wantTo('see the new amendments');

// Make Ä6 and Ä7 visible
$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 274, IMotion::STATUS_SUBMITTED_SCREENED);
$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 276, IMotion::STATUS_SUBMITTED_SCREENED);

$I->wait(4);

$I->seeElement('.toggleAmendment274.btn-default');
$I->seeElement('.toggleAmendment276.btn-default');
$I->dontSeeElement('.toggleAmendment274.toggleActive');
$I->seeElement('#newAmendmentAlert');

$I->clickJS('#newAmendmentAlert .closeLink');
$I->wait(1);
$I->dontSeeElement('#newAmendmentAlert');


$I->wantTo('embed a change of Ä6');

$I->dontSee('Schooe', '#paragraphWrapper_4_0');
$I->clickJS('#paragraphWrapper_4_0 .amendmentStatus274 .toggleAmendment');
$I->wait(0.5);
$I->see('Schooe', '#paragraphWrapper_4_0 ins');
$I->executeJS('$("#paragraphWrapper_4_0 [data-cid=1]").trigger("mouseover"); $("button.accept").click();');
$I->wait(0.2);
$I->see('Schooe', '#paragraphWrapper_4_0');
$I->dontSee('Schooe', '#paragraphWrapper_4_0 ins');


$I->wantTo('make Ä6 and Ä7 invisible again');

$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 274, IMotion::STATUS_SUBMITTED_UNSCREENED);
$I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 276, IMotion::STATUS_SUBMITTED_UNSCREENED);

$I->wait(4);

$I->dontSeeElement('.toggleAmendment274');
$I->dontSeeElement('.toggleAmendment276');

$I->see('Schooe', '#paragraphWrapper_4_0'); // The applied change remains


$I->wantTo('ensure the change is visible if opening the draft');

// Prevent the alert from disturbing the window
$I->executeJS(' $(window).unbind("beforeunload");');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.sidebarActions .mergeamendments a');
$I->click('.draftExistsAlert a.btn');

$I->dontSeeElement('.toggleAmendment274');
$I->dontSeeElement('.toggleAmendment276');

$I->see('Schooe', '#paragraphWrapper_4_0'); // The applied change remains
