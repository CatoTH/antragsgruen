<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\plugins\openslides\AutoupdateSyncService;
use app\plugins\openslides\OpenslidesClient;
use app\plugins\openslides\SiteSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\Support\Helper\TestBase;

class OpenslidesClientTest extends TestBase
{
    /** @var array */
    protected array $osApiHistory;

    /** @var MockHandler */
    protected MockHandler $mockHandler;

    private function getClient(): OpenslidesClient
    {
        $this->osApiHistory = [];
        $history = Middleware::history($this->osApiHistory);
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $siteSettings = new SiteSettings([]);
        $siteSettings->osBaseUri = 'https://os.test/';

        return new OpenslidesClient($siteSettings, $guzzleClient);
    }

    private function getRequestNo(int $no): Request
    {
        return $this->osApiHistory[$no]['request'];
    }

    public function testLoginResponse_Success(): void
    {
        $client = $this->getClient();

        // This is the JSON returned by Openslides
        $successJson = '{
            "user_id":2,
            "guest_enabled":false,
            "user":{
                "vote_weight":"1.000000",
                "vote_delegated_from_users_id":[],
                "is_active":true,
                "number":"",
                "last_email_send":null,
                "is_committee":false,
                "is_present":true,
                "first_name":"Max",
                "vote_delegated_to_id":null,
                "gender":"",
                "title":"",
                "last_name":"Mustermann",
                "email":"",
                "groups_id":[2],
                "comment":"",
                "about_me":"",
                "id":2,
                "username":"demo",
                "default_password":"demo",
                "structure_level":"",
                "auth_type":"default"
            },
            "auth_type":"default",
            "permissions":[]
        }';
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], $successJson));

        $loginResponse = $client->login('username', 'password');

        $this->assertFalse($loginResponse->isGuestEnabled());
        $this->assertSame(2, $loginResponse->getUserId());
        $this->assertSame([2], $loginResponse->getUser()->getGroupsId());
        $this->assertSame(2, $loginResponse->getUser()->getId());
        $this->assertSame('demo', $loginResponse->getUser()->getUsername());
        $this->assertSame('Max', $loginResponse->getUser()->getFirstName());
        $this->assertSame('Mustermann', $loginResponse->getUser()->getLastName());
        $this->assertSame([], $loginResponse->getUser()->getVoteDelegatedFromUsersId());

        // This is the request we made to Openslides
        $request = $this->getRequestNo(0);
        $this->assertSame('apps/users/login/', $request->getUri()->getPath());
        $this->assertJsonStringEqualsJsonString('{"username":"username","password":"password"}', $request->getBody()->getContents());
    }

    public function testParseAutoupdaterCallbackParsing(): void
    {
        $json = file_get_contents(__DIR__.'/../Support/Data/openslides-autoupdate-fullload.json');
        $service = new AutoupdateSyncService();
        $data = $service->parseRequest($json);

        $this->assertCount(44, $data->getChanged()->getUsersUsers());
        $this->assertCount(5, $data->getChanged()->getUsersGroups());

        $this->assertSame(2, $data->getChanged()->getUsersGroups()[1]->getId());
        $this->assertSame('Admin', $data->getChanged()->getUsersGroups()[1]->getName());
        $this->assertSame([], $data->getChanged()->getUsersGroups()[1]->getPermissions());

        $this->assertSame([5], $data->getChanged()->getUsersUsers()[43]->getGroupsId());
        $this->assertSame('Vorstand', $data->getChanged()->getUsersUsers()[43]->getUsername());
        $this->assertSame(43, $data->getChanged()->getUsersUsers()[43]->getId());
    }
}
