<?php

namespace app\plugins\member_petitions;

use app\components\yii\MessageSource;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\settings\Layout;

class LayoutSettings extends Layout
{
    public function formatTitle(string $title): string
    {
        return $title;
    }

    public function setConsultation(Consultation $consultation): void
    {
        $this->consultation = $consultation;
        if ($consultation && count($this->breadcrumbs) === 0) {
            $this->breadcrumbs[UrlHelper::homeUrl()] = \Yii::t('member_petitions', 'bc');
            $url                                     = \Yii::$app->request->url;
            if (strpos($url, $consultation->urlPath) !== false) {
                $this->breadcrumbs[UrlHelper::createUrl('consultation/index')] = $consultation->titleShort;
            }
        }
        if ($consultation) {
            $language = substr($consultation->wordingBase, 0, 2);
            if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
                \Yii::$app->language = $language;
            }
        }
    }
}
