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
$I->dontSeeElement('.checkbox-label.value-applications.active');
$I->clickJS('.checkbox-label.value-motion');
$I->clickJS('.checkbox-label.value-applications');
$I->wait(0.2);
$I->dontSeeElement('.checkbox-label.value-motion.active');
$I->seeElement('.checkbox-label.value-applications.active');
$I->click('#panelFunctionality button.btn-next');

$I->click('#panelApplicationType .value-2');
$I->click('#panelApplicationType button.btn-next');

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

$I->see('Bewerben', '#sidebar .createMotion');
$I->click('#sidebar .createMotion');
$I->seeElement('.type5');

$I->logout();
$I->dontSee('Hallo auf Antragsgrün');
$I->dontSee('Test-Congress', 'h1');
$I->see('Wartungsmodus', 'h1');

$I->gotoConsultationHome(false, 'testcongress', 'testcongress');
$I->wantTo('check the imprint');
$I->click('#legalLink');
$I->see('I myself');
$I->see('My address');
