<?php

declare(strict_types=1);

namespace app\plugins\openslides\controllers;

use app\plugins\openslides\DTO\AutoupdateUpdate;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class AutoupdateController extends \app\controllers\Base
{
    public $enableCsrfValidation = false;

    private static function getSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer(),
            new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, new ReflectionExtractor()),
        ];

        return new Serializer($normalizers, $encoders);
    }

    /**
     * The purpose of this method is to make the parsing of the configured Serializer testable
     */
    public static function parseRequest(string $postedJson): AutoupdateUpdate
    {
        return self::getSerializer()->deserialize($postedJson, AutoupdateUpdate::class, 'json');
    }

    public function actionCallback(): ?string
    {
        if ($this->getHttpMethod() !== 'POST') {
            return $this->returnRestResponse(405, json_encode(['success' => false, 'error' => 'Only POST is allowed'], JSON_THROW_ON_ERROR));
        }

        $data = self::parseRequest($this->getPostBody());


        return $this->returnRestResponse(200, json_encode(['success' => true], JSON_THROW_ON_ERROR));
    }
}
