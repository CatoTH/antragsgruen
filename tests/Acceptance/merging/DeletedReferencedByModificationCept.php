<?php

// This tests the edge case that a modified version of an amendment exists where the base amendment was already deleted or withdrawn

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('set an amendment with a proposed procedure to invisible');
$I->loginAndGotoMotionList()->gotoAmendmentEdit(281);

$I->selectOption('#amendmentStatus', IMotion::STATUS_WITHDRAWN_INVISIBLE);
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->click('#sidebar .mergeamendments a');
$I->wait(0.2);
$I->dontSeeElement('.amendment281');
$I->clickJS('.toMergeAmendments .selectAll');
$I->wait(0.2);
$I->submitForm('.mergeAllRow', [], null);
$I->wait(0.5);

$I->dontSee('Ä3');

// This tests that the rendering of the Vue components works; wouldn't work if it breaks before it
$I->see('Ä4');
$I->see('Zombie', 'ins');
