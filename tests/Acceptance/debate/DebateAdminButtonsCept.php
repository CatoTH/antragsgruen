<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$tabDebated = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(1)';
$tabVoting  = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';

// The std-parteitag fixture ships with an ongoing debate on motion A2 ("O’zapft is!"), which has no
// speaking list and no voting yet - so the debated tab offers the "Activate" / "Create" variants.
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);


$I->wantTo('activate the speaking list directly from the debated tab');
$I->see('Redeliste aktivieren', '.currentDebateAdmin .manageSpeechBtn'); // no active list yet
$I->click('.currentDebateAdmin .manageSpeechBtn');
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8); // activated + switched to the tab

$I->wantTo('see the speaking-list button switch to "manage" once a list is active');
$I->click($tabDebated);
$I->waitForText('Redeliste verwalten', 5, '.currentDebateAdmin .manageSpeechBtn');
$I->click('.currentDebateAdmin .manageSpeechBtn'); // now just switches to the tab
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);


$I->wantTo('keep the debated-tab voting label in sync when assigning then unassigning a voting');
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
$I->selectOption('.votingTab #debateVotingSelectExisting', 'Ä2 or Ä3');
$I->wait(0.2); // let Vue enable the "assign" button
$I->click('.votingTab .votingCreate .votingAssignRow button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
// The debated tab now reflects the assigned voting
$I->click($tabDebated);
$I->waitForText('Abstimmung verwalten', 5, '.currentDebateAdmin .manageVotingBtn');
// Unassign again (allowed: the debated motion is not itself a voting item of this block)
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->click('.votingTab .votingCard .votingCardActions button');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
// Back on the debated tab, the label must revert to "Create voting" rather than stay "Manage voting"
$I->click($tabDebated);
$I->waitForText('Abstimmung anlegen', 5, '.currentDebateAdmin .manageVotingBtn');


$I->wantTo('create the voting directly from the debated tab');
$I->click($tabDebated);
$I->waitForElement('.currentDebateAdmin .manageVotingBtn', 5);
$I->see('Abstimmung anlegen', '.currentDebateAdmin .manageVotingBtn'); // no voting associated yet
$I->click('.currentDebateAdmin .manageVotingBtn');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8); // created + switched to the tab
$I->see('In Vorbereitung', '.votingTab .votingCard .votingCardStatus');
// The debated motion is now its own voting item, so unassigning would not clear the card - the
// "unassign" button is therefore hidden (it would otherwise be a no-op, leaving the voting box behind).
$I->dontSeeElement('.votingTab .votingCard .votingCardActions button');

$I->wantTo('see the voting button switch to "manage" once a voting is associated');
$I->click($tabDebated);
$I->waitForText('Abstimmung verwalten', 5, '.currentDebateAdmin .manageVotingBtn');
$I->click('.currentDebateAdmin .manageVotingBtn'); // now just switches to the tab
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);


$I->wantTo('see the button labels update when switching to another item without changing tabs');
$I->click($tabDebated);
$I->waitForText('Abstimmung verwalten', 5, '.currentDebateAdmin .manageVotingBtn'); // the motion has a voting
// Switch the debate to a free-text item (no voting) - this stays on the debated tab and only changes
// the current item, so the label must update on its own, not just after a tab switch.
$I->fillField('#debateAdminFreeText', 'Allgemeine Aussprache');
$I->click('.currentDebateAdmin .selectRow-free_text .rowButton button');
$I->waitForText('Allgemeine Aussprache', 5, '.currentDebateAdmin .debatedItem .title');
$I->waitForText('Abstimmung anlegen', 5, '.currentDebateAdmin .manageVotingBtn');
