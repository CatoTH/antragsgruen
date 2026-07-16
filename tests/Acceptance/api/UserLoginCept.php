<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\User;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$baseUri = str_replace(['{SUBDOMAIN}', '{PATH}'], ['stdparteitag', ''], AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
$client  = new Client([
    'base_uri' => $baseUri,
    RequestOptions::HTTP_ERRORS => false,
]);

$loginPath = 'rest/std-parteitag/user/login';


$I->wantTo('log in while the API is still disabled');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(403, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"success":false,"message":"Public API disabled"}', $request->getBody()->getContents());


$I->wantTo('enable the API');

$I->apiSetApiEnabled();


$I->wantTo('log in with valid credentials');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(200, $request->getStatusCode());

$body = json_decode($request->getBody()->getContents(), true);
$I->assertCount(2, $body);
$I->assertIsString($body['token']);
$I->assertNotEmpty($body['token']);
$I->assertIsInt($body['exp']);
$I->assertGreaterThan(time(), $body['exp']);

$token = $body['token'];


$I->wantTo('use the returned token to access the API as the logged-in user');

$request = $client->get('rest/user', [RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
$I->assertEquals(200, $request->getStatusCode());
$I->assertJsonStringEqualsJsonString('{"auth": "email:testuser@example.org"}', $request->getBody()->getContents());


$I->wantTo('log in with a wrong password');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'not-the-password']]);
$I->assertEquals(401, $request->getStatusCode());
$wrongPasswordBody = $request->getBody()->getContents();
$body = json_decode($wrongPasswordBody, true);
$I->assertFalse($body['success']);


$I->wantTo('log in with an unknown username');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'does-not-exist@example.org', 'password' => 'whatever']]);
$I->assertEquals(401, $request->getStatusCode());
$unknownUserBody = $request->getBody()->getContents();
$body = json_decode($unknownUserBody, true);
$I->assertFalse($body['success']);

// To prevent user enumeration, the response must not differ between an unknown username and a wrong password
$I->assertJsonStringEqualsJsonString($wrongPasswordBody, $unknownUserBody);


$I->wantTo('use a malformed bearer token');

$request = $client->get('rest/user', [RequestOptions::HEADERS => ['Authorization' => 'Bearer not-a-jwt']]);
$I->assertEquals(401, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);


$I->wantTo('log in with a malformed request body');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org']]);
$I->assertEquals(400, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);


$I->wantTo('log in while a forced password change is pending');

/** @var User $user */
$user     = User::findOne(['id' => 2]);
$settings = $user->getSettingsObj();
$settings->forcePasswordChange = true;
$user->setSettingsObj($settings);
$user->save();

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(403, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);

$settings->forcePasswordChange = false;
$user->setSettingsObj($settings);
$user->save();


$I->wantTo('log in with an unconfirmed e-mail address');

$user         = User::findOne(['id' => 2]);
$user->status = User::STATUS_UNCONFIRMED;
$user->save();

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(403, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);

$user->status = User::STATUS_CONFIRMED;
$user->save();


$I->wantTo('log in with two-factor authentication set up');

$user     = User::findOne(['id' => 2]);
$settings = $user->getSettingsObj();
$settings->secondFactorKeys = [[
    'type'   => 'totp',
    'secret' => trim((string) file_get_contents(__DIR__ . '/../../config/2fa.secret')),
]];
$user->setSettingsObj($settings);
$user->save();

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(403, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);

$settings->secondFactorKeys = null;
$user->setSettingsObj($settings);
$user->save();


$I->wantTo('log in again now that all preconditions are cleared');

$request = $client->post($loginPath, [RequestOptions::JSON => ['username' => 'testuser@example.org', 'password' => 'testuser']]);
$I->assertEquals(200, $request->getStatusCode());
$token = json_decode($request->getBody()->getContents(), true)['token'];


$I->wantTo('use a still-valid token of an account that has been deleted in the meantime');

$user = User::findOne(['id' => 2]);
$user->deleteAccount();

$request = $client->get('rest/user', [RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
$I->assertEquals(401, $request->getStatusCode());
$body = json_decode($request->getBody()->getContents(), true);
$I->assertFalse($body['success']);
