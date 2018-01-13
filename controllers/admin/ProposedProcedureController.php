<?php

namespace app\controllers\admin;

use app\components\ProposedProcedureAgenda;
use app\components\ProposedProcedureFactory;
use app\models\db\User;
use yii\web\Response;

class ProposedProcedureController extends AdminBase
{
    public static $REQUIRED_PRIVILEGES = [
        User::PRIVILEGE_CHANGE_PROPOSALS
    ];

    /**
     * @param int $agendaItemId
     * @return string
     */
    public function actionIndex($agendaItemId = 0)
    {
        if ($agendaItemId) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
            $proposalFactory = new ProposedProcedureFactory($this->consultation, $agendaItem);
        } else {
            $proposalFactory = new ProposedProcedureFactory($this->consultation);
        }

        return $this->render('index', [
            'proposedAgenda'      => $proposalFactory->create(),
        ]);
    }

    /**
     * @param int $agendaItemId
     * @return string
     */
    public function actionOds($agendaItemId = 0)
    {
        $filename = 'proposed-procedure';
        if ($agendaItemId) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
            $filename .= '-' . trim($agendaItem->getShownCode(true), "\t\n\r\0\x0b.");
            $proposalFactory = new ProposedProcedureFactory($this->consultation, $agendaItem);
        } else {
            $proposalFactory = new ProposedProcedureFactory($this->consultation);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=' . rawurlencode($filename));
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods', [
            'proposedAgenda'      => $proposalFactory->create(),
        ]);
    }
}
