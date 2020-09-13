<?php

declare(strict_types=1);

namespace app\plugins\discourse\controllers;

use app\controllers\Base;
use app\plugins\discourse\Module;
use app\plugins\discourse\Tools;

class AmendmentController extends Base
{
    /**
     * @param string $motionSlug
     * @param int $amendmentId
     *
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionGotoDiscourse(string $motionSlug, $amendmentId)
    {
        $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId);

        $discourseData = $amendment->getExtraDataKey('discourse');
        if (!$discourseData) {
            try {
                $discourseData = Tools::createAmendmentTopic($amendment);
            } catch (\Exception $e) {
                $this->showErrorpage(500, \Yii::t('discourse', 'error_create'));
            }
        }

        $discourseConfig = Module::getDiscourseConfiguration();
        $url = $discourseConfig['host'] . 't/' . $discourseData['topic_id'];

        return $this->redirect($url);
    }
}
