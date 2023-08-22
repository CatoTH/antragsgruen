<?php

declare(strict_types=1);

namespace app\plugins\openslides;

use app\components\Tools;
use app\models\exceptions\{Login, LoginInvalidUser};
use app\plugins\openslides\DTO\LoginResponse;
use GuzzleHttp\{Client, RequestOptions};

class OpenslidesClient
{
    public function __construct(
        private SiteSettings $siteSettings,
        private ?Client $client = null
    ) {
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client([
               'base_uri' => $this->siteSettings->osBaseUri,
            ]);
        }
        return $this->client;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws LoginInvalidUser
     * @throws Login
     */
    public function login(string $username, string $password): LoginResponse
    {
        $response = $this->getClient()->post('apps/users/login/', [
            RequestOptions::JSON => [
                "username" => $username,
                "password" => $password,
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatusCode() !== 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            if (isset($data['detail'])) {
                throw new LoginInvalidUser($data['detail']);
            } else {
                throw new Login('HTTP: ' . $response->getStatusCode());
            }
        }

        /** @var LoginResponse $loginResponse */
        $loginResponse = Tools::getSerializer()->deserialize($response->getBody()->getContents(), LoginResponse::class, 'json');
        return $loginResponse;
    }
}
