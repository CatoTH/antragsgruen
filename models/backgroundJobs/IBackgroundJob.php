<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

use app\components\Tools;
use app\models\db\{Consultation, Site};
use Symfony\Component\Serializer\Annotation\Ignore;

abstract class IBackgroundJob
{
    protected ?Consultation $consultation = null;
    protected ?Site $site = null;
    protected ?int $id = null;

    abstract public function getTypeId(): string;

    abstract public function execute(): void;

    /**
     * @return array<string, class-string<IBackgroundJob>>
     */
    public static function getAllBackgroundJobs(): array
    {
        return [
            SendNotification::TYPE_ID => SendNotification::class,
        ];
    }

    public function toJson(): string
    {
        $serializer = Tools::getSerializer();

        return $serializer->serialize($this, 'json');
    }

    /**
     * @Ignore
     */
    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    /**
     * @Ignore
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @Ignore
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public static function fromJson(int $id, string $typeId, ?int $siteId, ?int $consultationId, string $json): IBackgroundJob
    {
        $serializer = Tools::getSerializer();

        $class = self::getAllBackgroundJobs()[$typeId];

        /** @var IBackgroundJob $job */
        $job = $serializer->deserialize($json, $class, 'json');
        $job->id = $id;
        if ($siteId !== null) {
            $job->site = Site::findOne(['id' => $siteId]);
        }
        if ($consultationId !== null) {
            $job->consultation = Consultation::findOne(['id' => $consultationId]);
        }

        return $job;
    }
}
