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
$I->dontSeeElement('.checkbox-label.value-agenda.active');
$I->dontSeeElement('.checkbox-label.value-votings.active');
$I->dontSeeElement('.checkbox-label.value-documents.active');
$I->clickJS('.checkbox-label.value-agenda');
$I->clickJS('.checkbox-label.value-votings');
$I->clickJS('.checkbox-label.value-documents');
$I->wait(0.2);
$I->seeElement('.checkbox-label.value-agenda.active');
$I->seeElement('.checkbox-label.value-votings.active');
$I->seeElement('.checkbox-label.value-documents.active');
$I->click('#panelFunctionality button.btn-next');

$I->click('#panelSingleMotion .value-0');
$I->click('#panelSingleMotion button.btn-next');

$I->click('#panelMotionWho .value-3');
$I->click('#panelMotionWho button.btn-next');

$I->click('#panelMotionDeadline .value-1');
$I->fillField('#panelMotionDeadline .value-1 .date input', '30.12.2028 20:00');
$I->click('#panelMotionDeadline button.btn-next');

$I->click('#panelMotionScreening .value-1');
$I->click('#panelMotionScreening button.btn-next');

$I->click('#panelNeedsSupporters .value-1');
$I->fillField('#panelNeedsSupporters .value-1 .description input', 1);
$I->click('#panelNeedsSupporters button.btn-next');

$I->click('#panelHasAmendments .value-1');
$I->click('#panelHasAmendments button.btn-next');

$I->click('#panelAmendSinglePara .value-1');
$I->click('#panelAmendSinglePara button.btn-next');

$I->click('#panelAmendWho .value-3');
$I->click('#panelAmendWho button.btn-next');

$I->click('#panelAmendDeadline .value-1');
$I->fillField('#panelAmendDeadline .value-1 .date input', '30.11.2026 20:00');
$I->click('#panelAmendDeadline button.btn-next');

$I->click('#panelAmendScreening .value-1');
$I->click('#panelAmendScreening button.btn-next');

$I->click('#panelComments .value-1');
$I->click('#panelComments button.btn-next');

$I->click('#panelOpenNow .value-0');
$I->click('#panelOpenNow button.btn-next');

$I->fillField('#siteTitle', 'Test-Congress');
$I->fillField('#siteOrganization', 'My party');
$I->dontSeeElement('.subdomainError');
$I->fillField('#siteSubdomain', 'stdparteitag');
$I->executeJS('$("#siteSubdomain").change();');
$I->wait(0.5);
$I->seeElement('.subdomainError');
$I->see('stdparteitag', '.subdomainError');
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
$I->see('Tagesordnung', '.agendaItem');
$I->see('Änderungs&shy;anträge', '.deadlineCircle');
$I->see('30.11.2026 20:00', '.deadlineCircle');

$I->seeElement('#documentsLink');
$I->seeElement('#votingsLink');

$I->logout();
$I->dontSee('Hallo auf Antragsgrün');
$I->dontSee('Test-Congress', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->gotoConsultationHome(false, 'testcongress', 'testcongress');
$I->wantTo('check the imprint');
$I->click('#legalLink');
$I->see('I myself');
$I->see('My address');
