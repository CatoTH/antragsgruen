<?php

namespace app\plugins\european_youth_forum\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\mergeAmendments\Init;
use app\plugins\frauenrat\pdf\Frauenrat;
use app\plugins\frauenrat\pdf\FrauenratPdf;
use app\views\pdfLayouts\IPdfWriter;
use app\models\db\{ConsultationSettingsTag, ConsultationUserGroup, Motion, User};

class AdminController extends Base
{
    public function actionCreateYfjVoting()
    {
        echo "YFJ Voting";
    }

    public function actionCreateRollCall()
    {
        echo "Roll Call";
    }
}
