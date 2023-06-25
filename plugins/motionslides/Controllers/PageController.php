<?php

namespace app\plugins\motionslides\controllers;

use app\controllers\Base;
use app\models\exceptions\Access;
use app\models\settings\Privileges;
use app\models\db\{ConsultationText, IMotion, User};

class PageController extends Base
{
    private function findIMotionByPrefix(string $prefix): ?IMotion
    {
        foreach ($this->consultation->motions as $motion) {
            if ($prefix === $motion->getFormattedTitlePrefix()) {
                return $motion;
            }

            foreach ($motion->amendments as $amendment) {
                if ($prefix === $motion->getFormattedTitlePrefix() . ': ' . $amendment->getFormattedTitlePrefix()) {
                    return $amendment;
                }
            }
        }
        return null;
    }

    public function actionFromImotions(string $pageSlug): void
    {
        $page = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            throw new Access('No permissions to edit this page');
        }

        $motionPrefixes = explode(",", $this->getPostValue('imotions'));
        $errors = [];
        $imotions = [];
        foreach ($motionPrefixes as $motionPrefix) {
            $motionPrefix = trim($motionPrefix);
            if ($motionPrefix === '') {
                $errors[] = 'Empty motion prefix given';
                continue;
            }
            $imotion = $this->findIMotionByPrefix($motionPrefix);
            if (!$imotion) {
                $errors[] = 'Prefix not found: ' . $motionPrefix;
                continue;
            }

            $imotions[] = $imotion;
        }

        if (count($errors) > 0) {
            $this->getHttpSession()->setFlash('error', implode("\n", $errors));
        } elseif (count($imotions) > 0) {
            $this->getHttpSession()->setFlash('success', 'Success.');

            $html = \Yii::$app->controller->renderPartial(
                '@app/plugins/motionslides/views/imotion-page', ['imotions' => $imotions]
            );

            $page->text = $html;
            $page->save();
        }

        $this->redirect($page->getUrl());
    }
}
