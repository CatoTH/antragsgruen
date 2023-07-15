<?php

/** @var \Codeception\Scenario $scenario */
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$baseUri = str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
$client = new Client([
    'base_uri' => $baseUri,
    RequestOptions::HTTP_ERRORS => false,
]);

// Default: API is disabled

$request = $client->get('rest/std-parteitag/motion/Moving_test-47262/amendment/278');

$I->assertEquals(403, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"success":false,"message":"Public API disabled"}', $request->getBody()->getContents());


// Enable it

$I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->executeJS('$("#apiEnabled").prop("checked", true).trigger("change");');
$I->submitForm('#consultationAppearanceForm', [], 'save');


// Check that the API is now returning the correct result

$request = $client->get('rest/std-parteitag/motion/Moving_test-47262/amendment/278');

$I->assertEquals(200, $request->getStatusCode());

$baseUri = 'http://'. AcceptanceTester::ABSOLUTE_URL_DOMAIN . '/stdparteitag/'; // "stdparteitag" is the subdomain
$I->assertJsonStringEqualsJsonString('{
    "type": "amendment",
    "id": 278,
    "prefix": "\u00c41",
    "title": "\u00c41 zu A7: Moving test",
    "title_with_prefix": "\u00c41 zu A7: Moving test",
    "first_line": 5,
    "status_id": 3,
    "status_title": "<span class=\"screened\">Gepr\u00fcft</span>",
    "date_published": "2017-04-30T00:00:04+00:00",
    "motion": {
        "id": 117,
        "agenda_item": null,
        "prefix": "A7",
        "title": "Moving test",
        "title_with_intro": "Moving test",
        "title_with_prefix": "A7: Moving test",
        "initiators_html": "Testuser (Anonymous)",
        "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Moving_test-47262",
        "url_html": "' . $baseUri . 'std-parteitag/Moving_test-47262"
    },
    "supporters": [],
    "initiators": [
        {
            "type": "person",
            "name": "Mover",
            "organization": "Moving"
        }
    ],
    "initiators_html": "Mover (Moving)",
    "sections": [
        {
            "type": "TextSimple",
            "title": "Antragstext",
            "html": "<div class=\"text motionTextFormattings textOrig\"><h4 class=\"lineSummary\">Von Zeile 5 bis 6 l\u00f6schen:</h4><div><ul class=\"deleted\" style=\"color:#FF0000;text-decoration:line-through;\"><li value=\"1\">Es ist ein paradiesmatisches Land, in dem einem gebratene Satzteile in den Mund fliegen.</li></ul></div><h4 class=\"lineSummary\">Nach Zeile 10 einf\u00fcgen:</h4><div><ul class=\"inserted\" style=\"color:#008000;text-decoration:underline;\"><li>Es ist ein paradiesmatisches Land, in dem einem gebratene Satzteile in den Mund fliegen.</li></ul></div><h4 class=\"lineSummary\">Von Zeile 12 bis 21 l\u00f6schen:</h4><div><p>Der gro\u00dfe Oxmox riet ihr davon ab, da es dort wimmele von b\u00f6sen Kommata, wilden Fragezeichen und hinterh\u00e4ltigen Semikoli, doch das Blindtextchen<del style=\"color:#FF0000;text-decoration:line-through;\"> lie\u00df sich nicht beirren.</del></p><p class=\"deleted\" style=\"color:#FF0000;text-decoration:line-through;\">Es packte seine sieben Versalien, schob sich sein Initial in den G\u00fcrtel und machte sich auf den Weg. Als es die ersten H\u00fcgel des Kursivgebirges erklommen hatte, warf es einen letzten Blick zur\u00fcck auf die Skyline seiner Heimatstadt Buchstabhausen, die Headline von Alphabetdorf und die Subline seiner eigenen Stra\u00dfe, der Zeilengasse.</p><p><del style=\"color:#FF0000;text-decoration:line-through;\">Wehm\u00fctig lief ihm eine </del>rhetorische Frage \u00fcber die Wange, dann setzte es seinen Weg fort. Unterwegs traf es eine Copy.</p></div><h4 class=\"lineSummary\">Nach Zeile 30 einf\u00fcgen:</h4><div><p class=\"inserted\" style=\"color:#008000;text-decoration:underline;\">lie\u00df sich nicht beirren.</p><p class=\"inserted\" style=\"color:#008000;text-decoration:underline;\">Es packte seine sieben Versalien, schob sich sein Initial in den G\u00fcrtel und machte sich auf den Weg. Als es die ersten H\u00fcgel des Kursivgebirges erklommen hatte, warf es einen letzten Blick zur\u00fcck auf die Skyline seiner Heimatstadt Buchstabhausen, die Headline von Alphabetdorf und die Subline seiner eigenen Stra\u00dfe, der Zeilengasse.</p><p class=\"inserted\" style=\"color:#008000;text-decoration:underline;\">Wehm\u00fctig lief ihm eine</p></div></div>"
        },
        {
            "type": "TextSimple",
            "title": "Antragstext 2",
            "html": ""
        }
    ],
    "proposed_procedure": null,
    "url_json": "' . $baseUri . 'rest/std-parteitag/motion/Moving_test-47262/amendment/278",
    "url_html": "' . $baseUri . 'std-parteitag/Moving_test-47262/278"
}', $request->getBody()->getContents());
