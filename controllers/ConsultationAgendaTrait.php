<?php

namespace app\controllers;

use app\models\exceptions\NotFound;
use app\models\settings\Privileges;
use app\models\http\{JsonResponse, ResponseInterface, RestApiExceptionResponse};
use app\views\consultation\LayoutHelper;
use app\models\db\{Consultation, ConsultationAgendaItem, User};
use app\models\exceptions\FormError;

/**
 * @property Consultation $consultation
 * @method string showErrorpage(int $error, string $message)
 */
trait ConsultationAgendaTrait
{
    /**
     * @return int[]
     * @throws FormError
     */
    private function saveAgendaArr(array $arr, ?int $parentId): array
    {
        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                $item = ConsultationAgendaItem::findOne(['id' => $jsitem['id'], 'consultationId' => $this->consultation->id]);
                if (!$item) {
                    throw new FormError('Inconsistency - did not find given agenda item: ' . $jsitem['id']);
                }
            } else {
                continue;
            }

            $item->parentItemId = $parentId;
            $item->position     = $i;

            $item->save();
            $items[] = $item->id;

            $items = array_merge($items, $this->saveAgendaArr($jsitem['children'], $item->id));
        }

        return $items;
    }

    public function actionSaveAgendaOrderAjax(): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            return new RestApiExceptionResponse(403, 'No access');
        }

        $data = json_decode($this->getPostValue('data'), true);

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        foreach ($this->consultation->agendaItems as $item) {
            if (!in_array($item->id, $usedItems)) {
                $item->deleteWithAllDependencies();
            }
        }

        if ($this->consultation->cacheOneMotionAffectsOthers()) {
            $this->consultation->flushCacheWithChildren(['lines']);
        }
        $this->consultation->refresh();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
