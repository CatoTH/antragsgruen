<?php

namespace app\plugins\memberPetitions;

use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\settings\Layout;

class LayoutSettings extends Layout
{
    /**
     * @param string $title
     * @return string
     */
    public function formatTitle($title)
    {
        return $title;
    }

    /**
     * @param Consultation $consultation
     */
    public function setConsultation(Consultation $consultation)
    {
        $this->consultation = $consultation;
        if ($consultation && count($this->breadcrumbs) == 0) {
            $this->breadcrumbs[UrlHelper::homeUrl()] = \Yii::t('memberPetitions', 'bc');
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
