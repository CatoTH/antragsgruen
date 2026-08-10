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
$I->see('O’zapft is!', '.currentDebateAdmin .debatedItem .title');


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
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->see('Ä2 or Ä3', '.votingTab .votingCard .votingCardTitle');
$I->see('Abstimmung verwalten', '.votingTab .votingCard'); // link into the full voting administration
// The motion is not itself a voting item, so unassigning returns to the create/assign UI
$I->click('.votingTab .votingCard .votingCardActions button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);


$I->wantTo('create a fresh voting for the debated motion');
$I->click('.votingTab .votingCreate > button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->see('O’zapft is!', '.votingTab .votingCard .votingCardTitle');
$I->see('In Vorbereitung', '.votingTab .votingCard .votingCardStatus'); // created in preparing state, not opened


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
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->see('Ä1', '.votingTab .votingCard .votingCardTitle');
$I->see('In Vorbereitung', '.votingTab .votingCard .votingCardStatus');


$I->wantTo('debate free text, which uses the generic fallback speaking list');
$I->click($tabDebated);
$I->waitForElement('#debateAdminFreeText', 5);
$I->fillField('#debateAdminFreeText', 'Allgemeine Aussprache zum Haushalt');
$I->click('.currentDebateAdmin .selectRow-free_text .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->see('Allgemeine Aussprache zum Haushalt', '.currentDebateAdmin .debatedItem .title');

$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);


$I->wantTo('switch the debate back to a motion and check the user-facing widget');
$I->click($tabDebated);
$I->waitForElement('#debateAdminSelect-motion', 5);
$I->selectOption('#debateAdminSelect-motion', ['value' => '2']); // A2: O’zapft is!
$I->click('.currentDebateAdmin .selectRow-motion .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->see('O’zapft is!', '.currentDebateAdmin .debatedItem .title');

$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->dontSeeElement('.currentDebateAdmin'); // regular users do not get the moderation widget
$I->waitForElement('.currentDebateInline .debatedItem', 5);
$I->see('O’zapft is!', '.currentDebateInline .debatedItem .title');


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
$I->see('Sonstiges', '.currentDebateAdmin .debatedItem .title');

$I->wantTo('open the speaking list for the debated agenda item');
$I->click($tabSpeech);
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);

$I->wantTo('create a voting for the agenda item via the free-text question form');
$I->click($tabVoting);
$I->waitForElement('.votingTab #debateVotingQuestion', 8);
$I->fillField('.votingTab #debateVotingQuestion', 'Sollen wir die Sitzung vertagen?');
$I->click('.votingTab .votingCreate .input-group button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->see('Sollen wir die Sitzung vertagen?', '.votingTab .votingCard .votingCardTitle');
$I->see('In Vorbereitung', '.votingTab .votingCard .votingCardStatus');

$I->wantTo('confirm the debated agenda item is visible to a regular user');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome(true, 'parteitag', 'parteitag');
$I->dontSeeElement('.currentDebateAdmin');
$I->waitForElement('.currentDebateInline .debatedItem', 5);
$I->see('Sonstiges', '.currentDebateInline .debatedItem .title');
