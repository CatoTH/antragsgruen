<?php

namespace app\controllers;

class ConsultationController extends Base
{
    /**
     * @param string $siteId
     */
    public function actionIndex($siteId)
    {
        echo $siteId;
    }
}
