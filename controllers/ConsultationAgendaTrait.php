<?php

namespace app\controllers;

use app\models\exceptions\NotFound;
use app\views\consultation\LayoutHelper;
use app\models\db\{Consultation, ConsultationAgendaItem, User};
use app\models\exceptions\FormError;
use yii\web\Response;

/**
 * @property Consultation $consultation
 * @method string showErrorpage(int $error, string $message)
 */
trait ConsultationAgendaTrait
{
    /**
     * @param array $arr
     * @param int|null $parentId
     *
     * @return int[]
     * @throws FormError
     */
    private function saveAgendaArr(array $arr, ?int $parentId)
    {
        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                /** @var ConsultationAgendaItem $item */
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

    public function actionSaveAgendaItemAjax(string $itemId)
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            return $this->showErrorpage(403, 'No access');
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $itemId = intval($itemId);
        $data   = json_decode(\Yii::$app->request->post('data'), true);

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
            /** @var ConsultationAgendaItem $item */
            $item = ConsultationAgendaItem::findOne(['id' => $itemId, 'consultationId' => $this->consultation->id]);
            if (!$item) {
                return json_encode(['success' => false, 'message' => 'Item not found']);
            }
        }

        if ($data['type'] === 'agendaItem') {
            $item->title = mb_substr($data['title'], 0, 250);
            $item->code  = mb_substr($data['code'], 0, 20);
            if (isset($data['time']) && preg_match('/^\d\d:\d\d$/siu', $data['time'])) {
                $item->time = $data['time'];
            } else {
                $item->time = null;
            }
            try {
                if ($data['motionType'] > 0 && $this->consultation->getMotionType($data['motionType'])) {
                    $item->motionTypeId = intval($data['motionType']);
                } else {
                    $item->motionTypeId = null;
                }
            } catch (NotFound $e) {
                $item->motionTypeId = null;
            }
            $settings                       = $item->getSettingsObj();
            $settings->inProposedProcedures = (!isset($data['inProposedProcedures']) || $data['inProposedProcedures']);
            $item->setSettingsObj($settings);

            $item->save();
            $item->refresh();

            ob_start();
            LayoutHelper::showAgendaItem($item, $this->consultation, true);
            $newHtml = ob_get_clean();
        } elseif ($data['type'] === 'date') {
            $item->title = $data['title'];
            if (isset($data['date']) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/siu', $data['date'])) {
                $item->time = $data['date'];
            } else {
                $item->time = null;
            }
            $item->motionTypeId = null;
            $item->code         = '';

            $item->save();
            $item->refresh();

            ob_start();
            LayoutHelper::showDateAgendaItem($item, $this->consultation, true);
            $newHtml = ob_get_clean();
        } else {
            return json_encode(['success' => false, 'message' => 'Unknown item type']);
        }

        return json_encode([
            'success' => true,
            'html'    => $newHtml,
        ]);
    }

    public function actionDelAgendaItemAjax(string $itemId)
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            return $this->showErrorpage(403, 'No access');
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $itemId = intval($itemId);
        /** @var ConsultationAgendaItem $item */
        $item = ConsultationAgendaItem::findOne(['id' => $itemId, 'consultationId' => $this->consultation->id]);
        if (!$item) {
            return json_encode(['success' => false, 'message' => 'Item not found']);
        }

        $item->deleteWithAllDependencies();

        return json_encode([
            'success' => true,
        ]);
    }

    public function actionSaveAgendaOrderAjax()
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            return $this->showErrorpage(403, 'No access');
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $data = json_decode(\Yii::$app->request->post('data'), true);

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            return json_encode([
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

        return json_encode([
            'success' => true,
        ]);
    }
}
