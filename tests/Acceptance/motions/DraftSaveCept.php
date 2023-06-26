<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('fill in some text and leave the page');

$page = $I->gotoConsultationHome();
$I->executeJS('for (var i in localStorage) { localStorage.removeItem(i); }');

$page->gotoMotionCreatePage();

$I->wait(3);

$I->fillField(['name' => 'sections[1]'], 'Draft title');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Some text</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Even more text</strong></p>");');

$I->wait(3);


$page = $I->gotoConsultationHome();


$I->wantTo('see the saved draft');
$page->gotoMotionCreatePage();
$I->wait(1);
$I->seeElement('#draftHint');
$I->see('Entwurf vom:', '#draftHint');
$I->dontSeeInField(['name' => 'sections[1]'], 'Draft title');
$I->dontSee('Some text');
$I->dontSee('Even more text');


$I->wantTo('restore the draft');
$I->click('#draftHint button.restore');
$I->seeBootboxDialog('Diesen Entwurf wiederherstellen?');
$I->acceptBootboxConfirm();
$I->seeInField(['name' => 'sections[1]'], 'Draft title');
$I->see('Some text');
$I->see('Even more text');
$I->dontSeeElement('#draftHint');
