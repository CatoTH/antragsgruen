<?php

declare(strict_types=1);

namespace app\controllers\admin;

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

    public function actionSave(): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        $serializer = Tools::getSerializer();

        try {
            /** @var AgendaItemApi[] $data */
            $data = $serializer->deserialize($this->getPostBody(), AgendaItemApi::class . '[]', 'json');
        } catch (NotNormalizableValueException $e) {
            return new RestApiResponse(400, ['error' => $e->getMessage()]);
        }

        $saver = new AgendaSaver($this->consultation);
        $saver->saveAgendaFromApi(null, $data);

        $savedAgenda = AgendaItemApi::getItemsFromConsultation($this->consultation);

        return new RestApiResponse(200, null, $serializer->serialize($savedAgenda, 'json'));
    }
}
