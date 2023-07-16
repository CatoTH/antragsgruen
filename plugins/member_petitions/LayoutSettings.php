<?php

namespace app\plugins\member_petitions;

use app\components\RequestContext;
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
        if (count($this->breadcrumbs) === 0) {
            $this->breadcrumbs['/'] = \Yii::t('member_petitions', 'bc');
            $url                    = RequestContext::getWebRequest()->url;
            if (str_contains($url, $consultation->urlPath)) {
                $this->breadcrumbs[UrlHelper::createUrl('consultation/index')] = $consultation->titleShort;
            }
        }
        $language = substr($consultation->wordingBase, 0, 2);
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }
    }
}
