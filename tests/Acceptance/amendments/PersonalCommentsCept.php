<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->gotoAmendment();

$I->wantTo('not see the comment section');
$I->dontSeeElement('.privateNoteOpener');
$I->dontSeeElement('.privateNotes form');

$I->wantTo('see the comment section logged in');
$I->loginAsStdUser();
$I->seeElement('.privateNoteOpener');
$I->dontSeeElement('.privateNotes form');
$I->dontSeeElement('#privatenote1');

$I->wantTo('write a note');
$I->click('.privateNoteOpener button');
$I->dontSeeElement('.privateNoteOpener');
$I->seeElement('.privateNotes form');
$I->fillField('.privateNotes form textarea', 'Some comment');
$I->submitForm('.privateNotes form', [], 'savePrivateNote');
$I->seeElement('#privatenote1');
$I->see('Some comment', '#privatenote1');

$I->wantTo('delete it again');
$I->click('#privatenote1 .btnEdit');
$I->fillField('.privateNotes form textarea', '');
$I->submitForm('.privateNotes form', [], 'savePrivateNote');

$I->dontSeeElement('#privatenote1');
