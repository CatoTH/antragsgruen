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

$request = $client->get('rest');

$I->assertEquals(403, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"success":false,"message":"Public API disabled"}', $request->getBody()->getContents());


// Enable it

$I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->dontSeeCheckboxIsChecked('#apiEnabled');
$I->dontSeeElement('.apiBaseUrl');
$I->dontSee($baseUri . 'rest');
$I->executeJS('$("#apiEnabled").prop("checked", true).trigger("change");');
$I->wait(0.3);
$I->seeElement('.apiBaseUrl');
$I->see($baseUri . 'rest');
$I->submitForm('#consultationAppearanceForm', [], 'save');


// Check that the API is now returning the correct result

$request = $client->get('rest');

$I->assertEquals(200, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('[
    {
        "title": "Test2",
        "title_short": "Test2",
        "date_published": "2015-11-16T22:35:58+00:00",
        "url_path": "std-parteitag",
        "url_json": "' . $baseUri . 'rest/std-parteitag",
        "url_html": "' . $baseUri . 'std-parteitag"
    }
]', $request->getBody()->getContents());
