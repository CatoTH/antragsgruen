<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->gotoMotion();

$I->wantTo('not see the comment section');
$I->dontSeeElement('.privateNoteOpener');
$I->dontSeeElement('.privateNotes form');
$I->dontSeeElement('.privateParagraphNoteOpener');

$I->wantTo('see the comment section logged in');
$I->loginAsStdUser();
$I->seeElement('.privateNoteOpener');
$I->dontSeeElement('.privateParagraphNoteOpener');
$I->dontSeeElement('.privateNotes form');
$I->dontSeeElement('#comm1');

$I->wantTo('write a note');
$I->click('.privateNoteOpener button');
$I->dontSeeElement('.privateNoteOpener');
$I->seeElement('.privateNotes form');
$I->seeElement('.privateParagraphNoteOpener');
$I->fillField('.privateNotes form textarea', 'Some comment');
$I->submitForm('.privateNotes form', [], 'savePrivateNote');
$I->seeElement('#comm1');
$I->seeElement('.privateParagraphNoteOpener');
$I->see('Some comment', '#comm1');

$I->wantTo('delete it again');
$I->click('#comm1 .btnEdit');
$I->fillField('.privateNotes form textarea', '');
$I->submitForm('.privateNotes form', [], 'savePrivateNote');

$I->seeElement('.privateNoteOpener');
$I->dontSeeElement('#comm1');
$I->dontSeeElement('.privateParagraphNoteOpener');

$I->wantTo('write a paragraph-note');
$I->dontSeeElement('.privateParagraphNoteHolder form');
$I->click('.privateNoteOpener button');
$I->click('#section_2_1 .privateParagraphNoteOpener button');
$I->seeElement('#section_2_1 .privateParagraphNoteHolder form');
$I->fillField('#section_2_1 .privateParagraphNoteHolder textarea', 'More content');
$I->submitForm('#section_2_1 .privateParagraphNoteHolder form', [], 'savePrivateNote');
$I->seeElement('#section_2_1 #comm2');
$I->see('More content', '#section_2_1 #comm2');

$I->wantTo('edit the note');
$I->dontSeeElement('#section_2_1 .privateParagraphNoteHolder textarea');
$I->click('#section_2_1 .privateParagraphNoteHolder .btnEdit');
$I->fillField('#section_2_1 .privateParagraphNoteHolder textarea', 'Changed content');
$I->submitForm('#section_2_1 .privateParagraphNoteHolder form', [], 'savePrivateNote');
$I->seeElement('#section_2_1 #comm2');
$I->see('Changed content', '#section_2_1 #comm2');

$I->wantTo('Delete the note');
$I->dontSeeElement('#section_2_1 .privateParagraphNoteHolder textarea');
$I->click('#section_2_1 .privateParagraphNoteHolder .btnEdit');
$I->fillField('#section_2_1 .privateParagraphNoteHolder textarea', '');
$I->submitForm('#section_2_1 .privateParagraphNoteHolder form', [], 'savePrivateNote');
$I->dontSeeElement('#section_2_1 #comm2');
$I->seeElement('.privateNoteOpener');
$I->dontSeeElement('.privateNotes form');
$I->dontSeeElement('.privateParagraphNoteOpener');


$I->wantTo('Disabled private notes');
$I->gotoConsultationHome();
$I->logout();
$page = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoAppearance();
$I->uncheckOption('#showPrivateNotes');
$page->saveForm();

$I->gotoMotion();
$I->dontSeeElement('.privateNoteOpener');
$I->gotoAmendment();
$I->dontSeeElement('.privateNoteOpener');


$I->wantTo('Enable private notes again');
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->checkOption('#showPrivateNotes');
$page->saveForm();

$I->gotoMotion();
$I->seeElement('.privateNoteOpener');
$I->gotoAmendment();
$I->seeElement('.privateNoteOpener');
