<?php

namespace app\controllers;

use app\components\Tools;
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
    private function saveAgendaArr($arr, $parentId)
    {
        $consultationId = intval($this->consultation->id);

        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                $condition = ['id' => intval($jsitem['id']), 'consultationId' => $consultationId];
                /** @var ConsultationAgendaItem $item */
                $item = ConsultationAgendaItem::findOne($condition);
                if (!$item) {
                    throw new FormError('Inconsistency - did not find given agenda item: ' . $condition);
                }
            } else {
                $item                 = new ConsultationAgendaItem();
                $item->consultationId = $consultationId;
            }

            $item->title = $jsitem['title'];
            $item->time  = null;
            if ($jsitem['type'] === 'std') {
                $item->code         = $jsitem['code'];
                $item->motionTypeId = ($jsitem['motionTypeId'] > 0 ? intval($jsitem['motionTypeId']) : null);
                if (isset($jsitem['time']) && preg_match('/^\d\d:\d\d$/siu', $jsitem['time'])) {
                    $item->time = $jsitem['time'];
                }
            }
            if ($jsitem['type'] === 'date') {
                $item->code = '';
                $item->time = Tools::dateBootstrapdate2sql($jsitem['date']);
                if (!$item->time) {
                    $item->time = '0000-00-00';
                }
            }
            $item->parentItemId = $parentId;
            $item->position     = $i;

            $settings                       = $item->getSettingsObj();
            $settings->inProposedProcedures = (!isset($jsitem['inProposedProcedures']) || $jsitem['inProposedProcedures']);
            $item->setSettingsObj($settings);

            $item->save();
            $items[] = $item->id;

            $items = array_merge($items, $this->saveAgendaArr($jsitem['children'], $item->id));
        }

        return $items;
    }

    protected function saveAgenda(): void
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            \Yii::$app->session->setFlash('error', 'No permissions to edit this page');

            return;
        }

        $data = json_decode(\Yii::$app->request->post('data'), true);
        if (!is_array($data)) {
            \Yii::$app->session->setFlash('error', 'Could not parse input');

            return;
        }

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());

            return;
        }

        foreach ($this->consultation->agendaItems as $item) {
            if (!in_array($item->id, $usedItems)) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $item->delete();
            }
        }

        if ($this->consultation->cacheOneMotionAffectsOthers()) {
            $this->consultation->flushCacheWithChildren(['lines']);
        }
        $this->consultation->refresh();

        \Yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
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
            $item = new ConsultationAgendaItem();
            $item->consultationId = $this->consultation->id;
            $item->position = intval($data['position']);
            $item->parentItemId = null;
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
            $item->title        = $data['title'];
            $item->code         = $data['code'];
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
            $item->title        = $data['title'];
            if (isset($data['date']) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/siu', $data['date'])) {
                $item->time = $data['date'];
            } else {
                $item->time = null;
            }
            $item->motionTypeId = null;
            $item->code = '';

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
            'html' => $newHtml,
        ]);
    }
}
