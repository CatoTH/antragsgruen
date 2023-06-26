<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable quota speech lists');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline');

$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();

$I->dontSeeCheckboxIsChecked('#hasSpeechLists');
$I->dontSeeElement('.quotas');
$I->executeJS('$("#hasSpeechLists").prop("checked", true).trigger("change");');
$I->wait(0.1);
$I->seeElement('.quotas');
$I->checkOption('#hasMultipleSpeechLists');
$I->executeJS('$("#speechRequiresLogin").prop("checked", true).trigger("change");');
$I->seeInField('.quotaName1 input', 'Offen / Männer');
$I->fillField('.quotaName1 input', 'Offener Platz');
$page->saveForm();

$I->gotoConsultationHome();

// The widget is visible now, but not yet open for application
$I->seeElement('.currentSpeechInline');
$I->see('Redeliste', '.currentSpeechInline');
$I->see('Frauen', '.waitingSubqueues');
$I->dontSee('Offen / Männer', '.waitingSubqueues');
$I->see('Offener Platz', '.waitingSubqueues');
$I->seeElement('.currentSpeechInline .notPossible'); // Applying is not possible yet
$I->seeElement('.currentSpeechInline .speechAdminLink');

$I->gotoMotion();
$I->seeElement('.currentSpeechFooter');
$I->see('Redeliste', '.currentSpeechFooter');
$I->see('Frauen', '.waitingMultiple');
$I->dontSee('Offen / Männer', '.waitingMultiple');
$I->see('Offener Platz', '.waitingMultiple');

// Goto admin widget & open for application
$I->click('.currentSpeechFooter .speechAdminLink');
$I->wait(0.1);

$I->seeElement('.slotActive.inactive .nameNobody'); // Nobody is speaking
$I->seeElement('.slotPlaceholder.inactive .nameNobody'); // Nobody is proposed
$I->see('Frauen', '.subqueues');
$I->see('Offener Platz', '.subqueues');
// Set to checked & trigger vue.js @onchange
$I->executeJS('var chkbox = document.querySelector(".toolbarBelowTitle.settings .settingsOpen input"), evt = document.createEvent("HTMLEvents"); chkbox.checked = true; evt.initEvent("change", false, true); chkbox.dispatchEvent(evt);');
$I->wait(0.1);

// Applying / withdrawing is now possible
$I->gotoConsultationHome();
$I->seeElement('.currentSpeechInline');
$I->dontSeeElement('.currentSpeechInline .appliedMe');
$I->see('Redeliste', '.currentSpeechInline');
$I->see('0', '.currentSpeechInline .number');
$I->dontSeeElement('.currentSpeechInline .notPossible');
$I->see('Bewerben', '.waitingSubqueues .applied button');
$I->executeJS('var btn = document.querySelectorAll(".waitingSubqueues .applied button").item(1), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->wait(0.1);
$I->seeInField('#speechRegisterName2', 'Testadmin');
$I->executeJS('var form = document.querySelectorAll(".waitingSubqueues form").item(0), evt = document.createEvent("HTMLEvents"); evt.initEvent("submit", false, true); form.dispatchEvent(evt);');
$I->wait(0.1);
$I->seeElement('.currentSpeechInline .appliedMe');
$I->see('1', '.currentSpeechInline .number');
$I->see('Testadmin', '.currentSpeechInline .nameList');
$I->executeJS('var btn = document.querySelectorAll(".waitingSubqueues .btnWithdraw").item(0), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->see('0', '.currentSpeechInline .number');
$I->dontSee('Testadmin', '.currentSpeechInline');

// Testing administration functions
$I->click('.currentSpeechInline .speechAdminLink');

$I->dontSeeElement('.subqueueAdder form');
$I->executeJS('var btn = document.querySelectorAll(".subqueues .adderOpener").item(0), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->wait(0.1);
$I->seeElement('.subqueueAdder form');
$I->fillField('#subqueueAdderName1', 'Testperson');
$I->executeJS('var btn = document.querySelectorAll(".subqueues .subqueueAdder form").item(0), evt = document.createEvent("HTMLEvents"); evt.initEvent("submit", false, true); btn.dispatchEvent(evt);');
$I->wait(0.1);

$I->see('Testperson', '.slotPlaceholder.active');
$I->seeElement('.slotActive.inactive');

$I->executeJS('var btn = document.querySelectorAll(".slotPlaceholder.active").item(0), evt = document.createEvent("HTMLEvents"); evt.initEvent("click", false, true); btn.dispatchEvent(evt);');
$I->wait(0.2);

$I->see('Testperson', '.slotEntry.slotActive');
$I->seeElement('.slotPlaceholder.inactive');

$I->gotoConsultationHome();
$I->see('Testperson', '.currentSpeechInline .activeSpeaker');

$I->gotoMotion();
$I->see('Testperson', '.currentSpeechFooter .activeSpeaker');
