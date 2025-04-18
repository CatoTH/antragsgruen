<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\components\LiveTools;
use app\models\api\AgendaItem as AgendaItemApi;
use app\models\settings\Privileges;
use app\models\http\{HtmlResponse, RestApiResponse};
use app\components\Tools;
use app\models\forms\AgendaSaver;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class AgendaController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_AGENDA,
    ];

    public function actionIndex(): HtmlResponse
    {
        return new HtmlResponse($this->render('index', ['consultation' => $this->consultation]));
    }

    public function actionRestIndex(): RestApiResponse
    {
        $this->handleRestHeaders(['POST', 'GET'], true);

        $serializer = Tools::getSerializer();

        if ($this->getHttpMethod() === 'POST') {
            try {
                /** @var AgendaItemApi[] $data */
                $data = $serializer->deserialize($this->getPostBody(), AgendaItemApi::class . '[]', 'json');
            } catch (NotNormalizableValueException $e) {
                return new RestApiResponse(400, ['error' => $e->getMessage()]);
            }

            $saver = new AgendaSaver($this->consultation);
            $saver->saveAgendaFromApi(null, $data);
        }

        $savedAgenda = AgendaItemApi::getItemsFromConsultation($this->consultation);
        LiveTools::sendAgenda($this->consultation, $savedAgenda);

        return new RestApiResponse(200, null, $serializer->serialize($savedAgenda, 'json'));
    }
}
