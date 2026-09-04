<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

// The voting administration embedded in the debate has to follow changes it did not make itself -
// another moderator on another screen, or the voting administration page.

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$tabVoting = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';
$embedded = '.currentDebateAdmin .votingTab .embeddedVotingAdmin';

$I->wantTo('create a voting for the debated item');
$I->loginAndGotoStdAdminPage();
$I->gotoConsultationHome();
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
$I->click('.votingTab .votingCreate > button');
$I->waitForElement($embedded . ' .voting', 8);
$I->seeElement($embedded . ' .btnOpen'); // still being prepared

$I->wantTo('open it from somewhere else, and see this widget notice');
// Straight to the endpoint, with the JWT of this page - what another moderator's browser would send,
// and deliberately not through the widget, whose own response would update it either way
$I->executeJS('
    const meta = document.head.querySelector("meta[name=user-jwt-config]");
    if (!meta) {
        throw new Error("This page provides no JWT");
    }
    const config = JSON.parse(meta.getAttribute("content"));
    return await fetch("/stdparteitag/rest/std-parteitag/votings/' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . '/settings", {
        method: "POST",
        headers: {"Authorization": "Bearer " + config.token, "Content-Type": "application/json; charset=utf-8"},
        body: JSON.stringify({op: "update-status", status: "open"}),
    }).then(response => response.text());
');

// Nothing is clicked in between: this only appears if the channel reached the embedded widget
$I->waitForElement($embedded . ' .btnClosePubOpener', 8);
$I->seeElement($embedded . ' .alert-success');
$I->dontSeeElement($embedded . ' .btnOpen');


$I->wantTo('see that an event about a single voting hands the widgets a new list');
// The step above went through a poll, which builds a new list anyway. An event about one voting is
// merged into the list instead - the path a Live server uses, and the only one left to a client that
// has stopped polling because it is connected. If that merge changed the list in place, a widget
// holding it would re-derive nothing from it and never notice the event, which is asserted here
// directly rather than through the widget: driving it through the DOM cannot tell the merge apart
// from the poll that follows a moment later.
//
// No Live server runs in the test environment, so the event is published into the module by hand.
// It is cached by URL, so this is the same instance and the same channel the widget is registered on.
$handsOutANewList = $I->executeJS('
    const liveData = await import("/js/modules/shared/LiveData.js");
    const seen = [];
    const handle = liveData.registerListener("admin", "voting", {onData: (votings) => seen.push(votings)});
    const votingId = ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ';
    const now = (new Date()).getTime();

    handle.publishChange({id: votingId, status: "closed_published", current_time: now});
    handle.publishChange({id: votingId, status: "closed_unpublished", current_time: now + 1});
    handle.unregister();

    if (seen.length !== 2) {
        return "expected two events, got " + seen.length;
    }
    if (seen[0] === seen[1]) {
        return "the same list was handed out twice - a merged event is invisible to whoever holds it";
    }
    if (seen[1].find(voting => voting.id === votingId).status !== "closed_unpublished") {
        return "the event was not merged into the list";
    }
    return "ok";
');
$I->assertSame('ok', $handsOutANewList);
