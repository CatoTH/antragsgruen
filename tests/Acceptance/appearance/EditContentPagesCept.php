<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\UserGroups;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->dontSeeElement('#mainmenu .addPage');
$I->loginAsConsultationAdmin();
$I->seeElement('#mainmenu .addPage');
$I->click('#mainmenu .addPage a');

$I->wantTo('create a new content page');
$I->fillField('.createPageForm #contentUrl', 'about');
$I->fillField('.createPageForm #contentTitle', 'About');
$I->submitForm('.createPageForm', [], 'create');

$I->wantTo('edit the new page');
$I->see('About', 'h1');
$I->see('About', '#mainmenu .page' . AcceptanceTester::FIRST_FREE_CONTENT_ID);
$I->click('.editCaller');
$I->wait(1);
$I->seeElement('.contentSettingsToolbar');
$I->executeJS('CKEDITOR.instances.stdTextHolder.setData("<p>New text</p>");');
$I->fillField('.contentSettingsToolbar #contentUrl', 'images');
$I->fillField('.contentSettingsToolbar #contentTitle', 'Images');
$I->seeCheckboxIsChecked("//input[@name='inMenu']");
$I->dontSeeElement('.userGroupSelect');
$I->selectOption('#policyReadPage', UserGroups::getPolicyID());
$I->wait(0.2);
$I->seeElement('.userGroupSelect');
$I->executeJS('document.querySelector("#policyReadPageGroups").selectize.addItem(3)');
$I->assertSame(1, $I->executeJS('return document.querySelector("#policyReadPageGroups").selectize.items.length'));
$I->click('.submitBtn');
$I->wait(1);

$I->wantTo('see the changes');
$I->dontSeeElement('.contentSettingsToolbar');
$I->see('Images', 'h1');
$I->see('Images', '#mainmenu .page' . AcceptanceTester::FIRST_FREE_CONTENT_ID);
$I->see('New text', '.content');

$I->wantTo('only see it with the correct permissions');
$I->logout();
$I->loginAsStdUser();
$I->dontSee('Images', '#mainmenu .page' . AcceptanceTester::FIRST_FREE_CONTENT_ID);
$I->gotoContentPage('images');
$I->see('Kein Zugriff auf diese Seite');
$I->logout();

$I->loginAsProposalAdmin();
$I->see('Images', '#mainmenu .page' . AcceptanceTester::FIRST_FREE_CONTENT_ID);
$I->gotoContentPage('images');
$I->see('New text');

$I->wantTo('remove it from the menu');
$I->logout();
$I->loginAsStdAdmin();
$I->click('.editCaller');
$I->wait(1);
$I->executeJS('$("#contentUrl").focus();'); // Make the floating panel disappear
$I->wait(0.5);
$I->checkOption("//input[@name='allConsultations']");
$I->uncheckOption("//input[@name='inMenu']");
$I->click('.submitBtn');
$I->wait(1);
$I->see('Images', 'h1');
$I->dontSee('Images', '#mainmenu .page' . AcceptanceTester::FIRST_FREE_CONTENT_ID);

$I->wantTo('delete it again');
$I->click('.deletePageForm button');
$I->seeBootboxDialog('Diese Seite wirklich löschen?');
$I->acceptBootboxConfirm();
$I->seeElement('.createPageForm');
$I->dontSee('Images');
$I->dontSee('About');
