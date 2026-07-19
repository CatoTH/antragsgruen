<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate the "Currently Debated" widget as an admin');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentDebateInline');
$I->dontSeeElement('.currentDebateAdmin');

$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->dontSeeCheckboxIsChecked('#hasCurrentlyDebated');
$I->checkOption('#hasCurrentlyDebated');
$page->saveForm();
$I->seeCheckboxIsChecked('#hasCurrentlyDebated');

// Both the admin and the user widget are now shown; no debate is running yet
$I->gotoConsultationHome();
$I->seeElement('.currentDebateAdmin');
$I->seeElement('.currentDebateInline');
$I->see('Aktuell findet keine Debatte statt', '.currentDebateInline');


$I->wantTo('set the first motion as the currently debated one');
$I->waitForElement('#debateAdminSelect-motion', 5);
$I->selectOption('#debateAdminSelect-motion', 'A2: O’zapft is!');
$I->click('.currentDebateAdmin .selectRow-motion .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->see('O’zapft is!', '.currentDebateAdmin .debatedItem .title');

// After a reload, the user-facing widget shows the debated motion (server-rendered initial state)
$I->gotoConsultationHome();
$I->see('O’zapft is!', '.currentDebateInline .debatedItem .title');


$I->wantTo('raise a secondary motion as a regular user');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->see('O’zapft is!', '.currentDebateInline .debatedItem .title');
$I->dontSeeElement('.currentDebateAdmin');

$I->waitForElement('.raiseSecondaryMotion button', 5);
$I->see('Antrag stellen', '.raiseSecondaryMotion button');
$I->click('.raiseSecondaryMotion button');

$I->waitForElement('.bootbox .raiseSecondaryMotionForm', 5);
$I->waitForJS('return !!(window.CKEDITOR && CKEDITOR.instances.sections_2_wysiwyg && CKEDITOR.instances.sections_2_wysiwyg.status === "ready");', 5);
$I->fillField('#sections_1', 'Mein Verfahrensantrag');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p>Wir beantragen die sofortige Abstimmung.</p>");');
$I->executeJS('$(".bootbox .modal-footer .btn-primary").trigger("click");');

$I->waitForText('Der Verfahrensantrag wurde gestellt.', 5, '.bootbox');
$I->acceptBootboxAlert();

// The secondary motion was created as a regular motion in the name of the current user
$I->gotoConsultationHome();
$I->see('Mein Verfahrensantrag');
$I->click('.motionLink' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see(mb_strtoupper('Mein Verfahrensantrag'), 'h1');
$I->see('Wir beantragen die sofortige Abstimmung.');
$I->see('Testuser');
