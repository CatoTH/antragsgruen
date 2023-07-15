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
$I->dontSeeElement('.checkbox-label.value-speech.active');
$I->clickJS('.checkbox-label.value-speech');
$I->wait(0.2);
$I->seeElement('.checkbox-label.value-speech.active');
$I->click('#panelFunctionality button.btn-next');

$I->click('#panelSingleMotion .value-0');
$I->click('#panelSingleMotion button.btn-next');

$I->click('#panelMotionWho .value-3');
$I->click('#panelMotionWho button.btn-next');

$I->click('#panelMotionDeadline .value-0');
$I->click('#panelMotionDeadline button.btn-next');

$I->click('#panelMotionScreening .value-1');
$I->click('#panelMotionScreening button.btn-next');

$I->click('#panelNeedsSupporters .value-0');
$I->click('#panelNeedsSupporters button.btn-next');

$I->click('#panelHasAmendments .value-0');
$I->click('#panelHasAmendments button.btn-next');

$I->click('#panelComments .value-1');
$I->click('#panelComments button.btn-next');

$I->click('#panelSpeechLogin .value-0');
$I->click('#panelSpeechLogin button.btn-next');

$I->click('#panelSpeechQuotas .value-1');
$I->click('#panelSpeechQuotas button.btn-next');

$I->click('#panelOpenNow .value-0');
$I->click('#panelOpenNow button.btn-next');

$I->fillField('#siteTitle', 'Test-Congress');
$I->fillField('#siteOrganization', 'My party');
$I->fillField('#siteSubdomain', 'testcongress');
$I->executeJS('$("#siteSubdomain").change();');
$I->wait(0.5);
$I->fillField('#siteContact', 'I myself' . "\n" . 'My address');

$I->submitForm('form.siteCreate', [], 'create');

$I->see('Die Veranstaltung wurde angelegt.');


$I->wantTo('open the consultation');
$I->submitForm('.createdForm', [], '');

$I->see('Hallo auf Antragsgrün');
$I->see('Test-Congress', 'h1');


$I->see('Redeliste', '.currentSpeechInline');
$I->see('Frauen', '.waitingSubqueues');
$I->see('Offen / Männer', '.waitingSubqueues');

$I->see('Antrag stellen', '#sidebar');
