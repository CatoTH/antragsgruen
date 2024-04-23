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

    public function actionSaveAgendaItemAjax(int $itemId): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            return new RestApiExceptionResponse(403, 'No access');
        }

        $isResolutionList = ($this->consultation->getSettings()->startLayoutResolutions === \app\models\settings\Consultation::START_LAYOUT_RESOLUTIONS_DEFAULT);
        $data = json_decode($this->getPostValue('data'), true, 512, JSON_THROW_ON_ERROR);

        if ($itemId === -1) {
            $item                 = new ConsultationAgendaItem();
            $item->consultationId = $this->consultation->id;
            $item->position       = intval($data['position']);
            $item->parentItemId   = null;
            if ($data['parentId']) {
                $parent = ConsultationAgendaItem::findOne(['id' => $data['parentId'], 'consultationId' => $this->consultation->id]);
                if ($parent) {
                    $item->parentItemId = $parent->id;
                }
            }
        } else {
            $item = ConsultationAgendaItem::findOne(['id' => $itemId, 'consultationId' => $this->consultation->id]);
            if (!$item) {
                return new JsonResponse(['success' => false, 'message' => 'Item not found']);
            }
        }

        if ($data['type'] === 'agendaItem') {
            $item->title = mb_substr($data['title'], 0, 250);
            $item->code  = mb_substr($data['code'], 0, 20);
            if (isset($data['time']) && preg_match('/^(?<hour>\d\d):(?<minute>\d\d)(?<ampm> (AM|PM))?$/siu', $data['time'], $matches)) {
                $hour = $matches['hour'];
                if (isset($matches['ampm']) && trim(strtolower($matches['ampm'])) === 'pm') {
                    $hour = (string)((int)$hour + 12);
                }
                $item->time = $hour . ':' . $matches['minute'];
            } else {
                $item->time = null;
            }
            try {
                if ($data['motionType'] > 0) {
                    $this->consultation->getMotionType($data['motionType']); // Throws an exception if not existent
                    $item->motionTypeId = intval($data['motionType']);
                } else {
                    $item->motionTypeId = null;
                }
            } catch (NotFound) {
                $item->motionTypeId = null;
            }
            $settings                       = $item->getSettingsObj();
            $settings->inProposedProcedures = (!isset($data['inProposedProcedures']) || $data['inProposedProcedures']);
            $item->setSettingsObj($settings);

            $item->save();

            if (isset($data['hasSpeakingList']) && $data['hasSpeakingList']) {
                $item->addSpeakingListIfNotExistant();
            } else {
                $item->removeSpeakingListsIfPossible();
            }

            $item->refresh();

            ob_start();
            LayoutHelper::showAgendaItem($item, $this->consultation, $isResolutionList, true);
            $newHtml = ob_get_clean();
        } elseif ($data['type'] === 'date') {
            $item->title = mb_substr($data['title'], 0, 250);
            if (isset($data['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/siu', $data['date'])) {
                $item->time = $data['date'];
            } else {
                $item->time = null;
            }
            $item->motionTypeId = null;
            $item->code         = '';

            $item->save();
            $item->refresh();

            ob_start();
            LayoutHelper::showDateAgendaItem($item, $this->consultation, $isResolutionList, true);
            $newHtml = ob_get_clean();
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Unknown item type']);
        }

        return new JsonResponse([
            'success' => true,
            'html'    => $newHtml,
        ]);
    }

    public function actionDelAgendaItemAjax(int $itemId): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            return new RestApiExceptionResponse(403, 'No access');
        }

        $item = ConsultationAgendaItem::findOne(['id' => $itemId, 'consultationId' => $this->consultation->id]);
        if (!$item) {
            return new JsonResponse(['success' => false, 'message' => 'Item not found']);
        }

        $item->deleteWithAllDependencies();

        return new JsonResponse([
            'success' => true,
        ]);
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
