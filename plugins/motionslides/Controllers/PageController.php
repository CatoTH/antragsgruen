<?php

namespace app\plugins\motionslides\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\exceptions\Access;
use app\models\majorityType\IMajorityType;
use app\models\policies\UserGroups;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;
use app\plugins\european_youth_forum\VotingHelper;
use app\models\db\{ConsultationText, ConsultationUserGroup, IMotion, User, VotingBlock, VotingQuestion};
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class PageController extends Base
{
    private function findIMotionByPrefix(string $prefix): ?IMotion
    {
        foreach ($this->consultation->motions as $motion) {
            if ($prefix === $motion->titlePrefix) {
                return $motion;
            }

            foreach ($motion->amendments as $amendment) {
                if ($prefix === $motion->titlePrefix . ': ' . $amendment->titlePrefix) {
                    return $amendment;
                }
            }
        }
        return null;
    }

    private function formatImotionForSlide(IMotion $IMotion): string
    {
        return '<li>' . Html::encode($IMotion->getTitleWithPrefix()) . '</li>';
    }

    public function actionFromImotions(string $pageSlug): void
    {
        $page = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
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

            $html = '<ul>';
            foreach ($imotions as $imotion) {
                $html .= $this->formatImotionForSlide($imotion);
            }
            $html .= '</ul>';

            $page->text = $html;
            $page->save();
        }

        $this->redirect($page->getUrl());
    }
}
