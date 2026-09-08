<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

// Tabs of the moderation widget: Debated Motion / Speaking List / Ongoing Voting / Protocol
$tabDebated = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(1)';
$tabSpeech  = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(2)';
$tabVoting  = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';

// The std-parteitag fixture ships with the feature enabled and an ongoing debate on motion A2 ("O’zapft is!").
// Admins see the moderation widget (.currentDebateAdmin); regular users/guests see the inline widget.
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->waitForText('O’zapft is!', 5, '.currentDebateAdmin .debatedItem .title');


$I->wantTo('open the speaking list of the debated motion');
$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);
$I->seeElement('.currentDebateAdmin .speechTab .toolbarBelowTitle');


$I->wantTo('assign an existing voting block to the debated motion, then unassign it again');
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
$I->selectOption('.votingTab #debateVotingSelectExisting', 'Ä2 or Ä3');
$I->wait(0.2); // let Vue enable the "assign" button
$I->click('.votingTab .votingCreate .votingAssignRow button');
// Holding the privilege to manage votings, this admin administers the voting right here rather than
// being sent to the voting administration page for it
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .voting', 8);
$I->see('Ä2 or Ä3', '.votingTab .embeddedVotingAdmin .voting h2');
$I->dontSee('Abstimmung verwalten', '.votingTab .votingCard');
// The motion is not itself a voting item, so unassigning returns to the create/assign UI
$I->click('.votingTab .votingCard .votingCardActions button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);


$I->wantTo('create a fresh voting for the debated motion');
$I->click('.votingTab .votingCreate > button');
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .voting', 8);
$I->see('O’zapft is!', '.votingTab .embeddedVotingAdmin .voting h2');
$I->seeElement('.votingTab .embeddedVotingAdmin .btnOpen'); // created in preparing state, not opened


$I->wantTo('open the voting without leaving the debate administration');
$I->clickJS('.votingTab .embeddedVotingAdmin .btnOpen');
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .btnClosePubOpener', 8);
$I->seeElement('.votingTab .embeddedVotingAdmin .alert-success');


$I->wantTo('debate an amendment: both a speaking list and a voting can be created for it');
$I->click($tabDebated);
$I->waitForElement('#debateAdminSelect-amendment', 5);
$I->selectOption('#debateAdminSelect-amendment', ['value' => '1']); // Ä1 zu A2
$I->click('.currentDebateAdmin .selectRow-amendment .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);

$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);

$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
$I->click('.votingTab .votingCreate > button');
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .voting', 8);
$I->see('Ä1', '.votingTab .embeddedVotingAdmin .voting h2');
$I->seeElement('.votingTab .embeddedVotingAdmin .btnOpen');


$I->wantTo('debate free text, which uses the generic fallback speaking list');
$I->click($tabDebated);
$I->waitForElement('#debateAdminFreeText', 5);
$I->fillField('#debateAdminFreeText', 'Allgemeine Aussprache zum Haushalt');
$I->click('.currentDebateAdmin .selectRow-free_text .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->waitForText('Allgemeine Aussprache zum Haushalt', 5, '.currentDebateAdmin .debatedItem .title');

$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);


$I->wantTo('switch the debate back to a motion and check the user-facing widget');
$I->click($tabDebated);
$I->waitForElement('#debateAdminSelect-motion', 5);
$I->selectOption('#debateAdminSelect-motion', ['value' => '2']); // A2: O’zapft is!
$I->click('.currentDebateAdmin .selectRow-motion .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->waitForText('O’zapft is!', 5, '.currentDebateAdmin .debatedItem .title');

$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->dontSeeElement('.currentDebateAdmin'); // regular users do not get the moderation widget
$I->waitForElement('.currentDebateInline .debatedItem', 5);
$I->waitForText('O’zapft is!', 5, '.currentDebateInline .debatedItem .title');


$I->wantTo('open the debate in the fullscreen projector, showing the read-only user view');
$I->click('.currentDebateInline .btnFullscreen');
$I->waitForElement('.fullscreenMainHolder .currentDebateContent .debatedItem', 8);
$I->waitForText('O’zapft is!', 5, '.fullscreenMainHolder .currentDebateContent .debatedItem .title');
$I->waitForText('Aktuell debattiert', 5, '.fullscreenMainHolder .imotionSelector'); // dropdown option is translated, not "UNKNOWN TRANSLATION"
$I->dontSeeElement('.fullscreenMainHolder .speechUser'); // read-only projection: the interactive apply UI is not rendered
// The speaking list of the debated motion was created above, but never activated - so the projector
// hides it, just like the inline widget does
$I->dontSeeElement('.fullscreenMainHolder .speechLists');
$I->click('.fullscreenMainHolder .closeBtn');
$I->waitForElementNotVisible('.fullscreenMainHolder', 5);


$I->wantTo('debate an agenda item on a consultation that has an agenda');
// std-parteitag has no agenda, so the agenda case is exercised on the "parteitag" consultation.
// Enabling the feature here only affects this test's database (it is reset per test).
$I->logout();
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage('parteitag', 'parteitag')->gotoAppearance();
$I->checkOption('#hasCurrentlyDebated');
$page->saveForm();

$I->gotoConsultationHome(true, 'parteitag', 'parteitag');
$I->waitForElement('#debateAdminSelect-agenda_item', 8);
$I->selectOption('#debateAdminSelect-agenda_item', ['value' => '7']); // "Sonstiges"
$I->click('.currentDebateAdmin .selectRow-agenda_item .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->waitForText('Sonstiges', 5, '.currentDebateAdmin .debatedItem .title');

$I->wantTo('open the speaking list for the debated agenda item');
$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);

$I->wantTo('create a voting for the agenda item via the free-text question form');
$I->click($tabVoting);
$I->waitForElement('.votingTab #debateVotingQuestion', 8);
$I->fillField('.votingTab #debateVotingQuestion', 'Sollen wir die Sitzung vertagen?');
$I->click('.votingTab .votingCreate .input-group button');
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .voting', 8);
$I->see('Sollen wir die Sitzung vertagen?', '.votingTab .embeddedVotingAdmin .voting h2');
$I->seeElement('.votingTab .embeddedVotingAdmin .btnOpen');

$I->wantTo('confirm the debated agenda item is visible to a regular user');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome(true, 'parteitag', 'parteitag');
$I->dontSeeElement('.currentDebateAdmin');
$I->waitForElement('.currentDebateInline .debatedItem', 5);
$I->waitForText('Sonstiges', 5, '.currentDebateInline .debatedItem .title');
