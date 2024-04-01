<?php

namespace app\models\settings;

class User implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $ppReplyTo = '';
    public ?array $voteWeightByConsultation = null;

    public function getVoteWeight(\app\models\db\Consultation $consultation): int
    {
        return $this->voteWeightByConsultation[$consultation->id] ?? 1;
    }

    public function setVoteWeight(\app\models\db\Consultation $consultation, int $weight): void
    {
        if (!$this->voteWeightByConsultation) {
            $this->voteWeightByConsultation = [];
        }
        $this->voteWeightByConsultation[$consultation->id] = $weight;
    }
}
