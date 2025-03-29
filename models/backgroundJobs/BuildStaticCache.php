<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

use app\components\mail\Base;
use app\components\RequestContext;
use app\models\db\Consultation;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;
use app\models\settings\AntragsgruenApp;
use app\views\consultation\LayoutHelper;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class BuildStaticCache extends IBackgroundJob
{
    public const TYPE_ID = 'BUILD_STATIC_CACHE';

    public const CACHE_ID_CONSULTATION_HOME = 'CONSULTATION_HOME';

    private ?string $result = null;

    public function __construct(
        public string $cacheId,
        ?Consultation $consultation,
        public bool $adminMode
    ) {
        $this->consultation = $consultation;
        $this->site = $this->consultation?->site;
    }

    public function getTypeId(): string
    {
        return self::TYPE_ID;
    }

    public function execute(): void
    {
        $this->result = match ($this->cacheId) {
            self::CACHE_ID_CONSULTATION_HOME => $this->getConsultationHome(),
            default => null,
        };
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    private function getConsultationHome(): string
    {
        return LayoutHelper::renderHomePageList($this->consultation, $this->adminMode);
    }
}
