<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\http\{BinaryFileResponse, ResponseInterface};
use app\models\proposedProcedure\AgendaVoting;
use app\models\settings\Privileges;
use app\models\db\{User, VotingBlock};
use app\components\Tools;

/**
 * The parts of the voting administration that are not the REST API: the result download, which the
 * browser follows as a link and which therefore authenticates by session rather than by JWT (see
 * app\controllers\rest\VotingController for everything else).
 */
class VotingController extends Base
{
    /**
     * @throws \Exception
     */
    private function getVotingBlockAndCheckAdminPermission(string $votingBlockId): VotingBlock
    {
        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_VOTINGS, null)) {
            throw new \Exception('Missing privileges');
        }

        $block = $this->consultation->getVotingBlock(intval($votingBlockId));
        if (!$block) {
            throw new \Exception('Voting block not found');
        }

        return $block;
    }

    public function actionDownloadVotingResults(string $votingBlockId, string $format): ResponseInterface
    {
        $this->handleRestHeaders(['GET'], true);
        try {
            $votingBlock = $this->getVotingBlockAndCheckAdminPermission($votingBlockId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }
        $agendaVoting = AgendaVoting::getFromVotingBlock($votingBlock);

        $formatResponse = match ($format) {
            'ods' => BinaryFileResponse::TYPE_ODS,
            'xlsx' => BinaryFileResponse::TYPE_XLSX,
            default => BinaryFileResponse::TYPE_HTML,
        };

        return new BinaryFileResponse(
            $formatResponse,
            $this->renderPartial('admin-download-results', ['agendaVoting' => $agendaVoting, 'format' => $format]),
            true,
            'voting-results-' . Tools::sanitizeFilename($votingBlock->title, true)
        );
    }

}
