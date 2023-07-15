<?php

use Tests\_pages\ManagerStartPage;
use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->openPage(ManagerStartPage::class);

$I->wantTo('go to creation form');
$I->loginAsStdAdmin();
$I->seeElement('.siteCreateForm');
$I->submitForm('.siteCreateForm', [], '');

$I->wantTo('click through the wizard');
$I->see('Welche Bestandteile soll die Seite haben?', '#panelFunctionality');

$I->seeElement('.checkbox-label.value-motion.active');
$I->dontSeeElement('.checkbox-label.value-manifesto.active');
$I->clickJS('.checkbox-label.value-motion');
$I->clickJS('.checkbox-label.value-manifesto');
$I->wait(0.2);
$I->dontSeeElement('.checkbox-label.value-motion.active');
$I->seeElement('.checkbox-label.value-manifesto.active');
$I->click('#panelFunctionality button.btn-next');

$I->click('#panelSingleMotion .value-1');
$I->click('#panelSingleMotion button.btn-next');

$I->click('#panelHasAmendments .value-0');
$I->click('#panelHasAmendments button.btn-next');

$I->click('#panelComments .value-1');
$I->click('#panelComments button.btn-next');

$I->click('#panelOpenNow .value-1');
$I->click('#panelOpenNow button.btn-next');


$I->fillField('#siteTitle', 'Test-Congress');
$I->fillField('#siteOrganization', 'My party');
$I->dontSeeElement('.subdomainError');
$I->fillField('#siteSubdomain', 'testcongress');
$I->fillField('#siteContact', 'I myself' . "\n" . 'My address');

$I->submitForm('form.siteCreate', [], 'create');

$I->see('Die Veranstaltung wurde angelegt.');
$I->see('Hier kannst du nun den Text eingeben', 'button');


$I->wantTo('create the motion');
$I->submitForm('.createdForm', [], '');
$I->wait(1);
$I->see('Kapitel anlegen', 'h1');
$I->fillField('#sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 0), 'Chapter title');
$ckfield = 'sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1);
$I->executeJS('CKEDITOR.instances.' . $ckfield . '_wysiwyg.setData("<p>Chapter content</p>");');
$I->fillField('#initiatorPrimaryName', 'My name');
$I->executeJS('$("[required]").removeAttr("required");');
$I->wait(1);
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->gotoConsultationHome(false, 'testcongress', 'testcongress');

$I->see('A1: Chapter title', 'h1');
$I->see('Chapter content');
$I->seeElement('#sidebar .amendmentCreate .onlyAdmins');
$I->seeElement('section.comments');
