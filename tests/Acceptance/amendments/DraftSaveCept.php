<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('fill in some text and leave the page');

$page = $I->gotoConsultationHome();
$I->executeJS('for (var i in localStorage) { localStorage.removeItem(i); }');

$page->gotoAmendmentCreatePage();

$I->wait(3);

$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "El Capitan");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');

$I->wait(3);


$page = $I->gotoConsultationHome();


$I->wantTo('see the saved draft');
$page->gotoAmendmentCreatePage();
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
$I->see('El Capitan');
$I->see('This is my reason');
$I->dontSeeElement('#draftHint');
