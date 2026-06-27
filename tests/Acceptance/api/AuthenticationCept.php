<?php

/** @var \Codeception\Scenario $scenario */
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->amOnPage(str_replace(
    ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
    ['stdparteitag', 'std-parteitag', 'token'],
    AcceptanceTester::ABSOLUTE_URL_TEMPLATE
));
$text = $I->grabPageSource();
$text = explode('"token":"', $text);
$token = explode('"', $text[1])[0];


$baseUri = str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
$client = new Client([
    'base_uri' => $baseUri,
    RequestOptions::HTTP_ERRORS => false,
]);


$request = $client->get('rest/user');
$I->assertEquals(403, $request->getStatusCode());

$request = $client->get('rest/user', [RequestOptions::HEADERS => [
    'Authorization' => 'Bearer ' . $token,
]]);
$I->assertEquals(200, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"auth": "email:testuser@example.org"}', $request->getBody()->getContents());

