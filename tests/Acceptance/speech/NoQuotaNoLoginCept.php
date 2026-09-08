<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable non-quota speech lists');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline');
$I->dontSeeElement('#speechLink');

$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();

$I->dontSeeCheckboxIsChecked('#hasSpeechLists');
$I->dontSeeElement('.quotas');
$I->executeJS('$("#hasSpeechLists").prop("checked", true).trigger("change");');
$I->waitForElement('.quotas', 10);
$I->seeElement('.quotas');
$I->uncheckOption('#activateFirstSpeechList');
$I->checkOption('#speechPage');
// The fixture has the "Currently debated" module on, which would take the place of this widget
$I->uncheckOption('#hasCurrentlyDebated');
$page->saveForm();

// The widget is not yet visible
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline');

$I->gotoMotion();
$I->dontSeeElement('.currentSpeechFooter');

$I->click('#speechLink');
$I->see('Die Redeliste ist nicht geöffnet');

// Goto admin widget & open for application
$I->click('.speechAdminLink');
$I->waitForElement('.speechAdmin', 10);

$I->seeElement('.slotActive.inactive .nameNobody'); // Nobody is speaking
$I->seeElement('.slotPlaceholder.inactive .nameNobody'); // Nobody is proposed
$I->see('Warteliste', '.subqueues');

$I->seeElement('.toolbarBelowTitle .settingsActive .inactive');
$I->executeJSAndWaitForBackend('var btn = document.querySelector(".toolbarBelowTitle .settingsActive button"), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');

// Set to checked & trigger vue.js @onchange
$I->executeJSAndWaitForBackend('var chkbox = document.querySelector(".toolbarBelowTitle.settings .settingsOpen input"), evt = document.createEvent("HTMLEvents"); chkbox.checked = true; evt.initEvent("change", false, true); chkbox.dispatchEvent(evt);');

// Applying / withdrawing is now possible
$I->gotoConsultationHome();
$I->seeElement('.currentSpeechInline');
$I->dontSeeElement('.currentSpeechInline .appliedMe');
$I->see('Redeliste', '.currentSpeechInline');
$I->see('0', '.currentSpeechInline .number');
$I->dontSeeElement('.currentSpeechInline .notPossible');
$I->see('Bewerben', '.waitingSingle .apply button');
$I->executeJS('var btn = document.querySelector(".waitingSingle .apply button"), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->waitForElement('#speechRegisterName-1', 10);
$I->seeInField('#speechRegisterName-1', 'Testadmin');
$I->executeJSAndWaitForBackend('var form = document.querySelector(".waitingSingle form"), evt = document.createEvent("HTMLEvents"); evt.initEvent("submit", false, true); form.dispatchEvent(evt);');
$I->seeElement('.currentSpeechInline .appliedMe');
$I->see('1', '.currentSpeechInline .number');
$I->see('Testadmin', '.currentSpeechInline .nameList');
$I->executeJSAndWaitForBackend('var btn = document.querySelector(".waitingSingle .btnWithdraw"), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->see('0', '.currentSpeechInline .number');
$I->dontSee('Testadmin', '.currentSpeechInline .nameList');

// Testing administration functions
$I->click('.currentSpeechInline .speechAdminLink');
$I->waitForElement('.speechAdmin', 10);

$I->dontSeeElement('.subqueueAdder form');
$I->executeJS('var btn = document.querySelector(".subqueues .adderOpener"), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->waitForElement('.subqueueAdder form', 10);
$I->seeElement('.subqueueAdder form');
$I->fillField('#subqueueAdderName-1', 'Testperson');
$I->executeJSAndWaitForBackend('var btn = document.querySelector(".subqueues .subqueueAdder form"), evt = document.createEvent("HTMLEvents"); evt.initEvent("submit", false, true); btn.dispatchEvent(evt);');

$I->see('Testperson', '.slotPlaceholder.active');
$I->seeElement('.slotActive.inactive');

$I->executeJSAndWaitForBackend('var btn = document.querySelector(".slotPlaceholder.active"), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');

$I->see('Testperson', '.slotEntry.slotActive');
$I->seeElement('.slotPlaceholder.inactive');

$I->gotoConsultationHome();
$I->see('Testperson', '.currentSpeechInline .activeSpeaker');

$I->gotoMotion();
$I->see('Testperson', '.currentSpeechFooter .activeSpeaker');
