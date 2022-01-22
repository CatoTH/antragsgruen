<?php

declare(strict_types=1);

namespace app\plugins\openslides;

use app\models\exceptions\{Login, LoginInvalidUser};
use app\plugins\openslides\DTO\LoginResponse;
use GuzzleHttp\{Client, RequestOptions};
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\{Encoder\JsonEncoder, Mapping\Factory\ClassMetadataFactory, Mapping\Loader\AnnotationLoader,
    Normalizer\ObjectNormalizer, Serializer, SerializerInterface};

class OpenslidesClient
{
    /** @var SiteSettings */
    private $siteSettings;

    /** @var Client|null */
    private $client;

    /** @var SerializerInterface|null */
    private $serializer = null;

    public function __construct(SiteSettings $siteSettings, ?Client $client = null)
    {
        $this->siteSettings = $siteSettings;
        $this->client = $client;
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

    private function getSerializer(): SerializerInterface
    {
        if ($this->serializer === null) {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
            $this->serializer = new Serializer($normalizers, $encoders);
        }
        return $this->serializer;
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
        $loginResponse = $this->getSerializer()->deserialize($response->getBody()->getContents(), LoginResponse::class, 'json');
        return $loginResponse;
    }
}
