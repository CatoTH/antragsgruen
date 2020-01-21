<?php

namespace app\controllers\admin;

use app\components\{HTMLTools, Tools};
use app\models\db\{AmendmentAdminComment, Consultation, IMotion, MotionAdminComment, User};
use app\models\proposedProcedure\Factory;
use yii\web\Response;

class ProposedProcedureController extends AdminBase
{
    public static $REQUIRED_PRIVILEGES = [
        User::PRIVILEGE_CHANGE_PROPOSALS
    ];

    /**
     * @param int $agendaItemId
     * @param null|int $expandId
     * @return string
     */
    public function actionIndex($agendaItemId = 0, $expandId = null)
    {
        $this->activateFunctions();
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $proposalFactory = new Factory($this->consultation, true, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, true);
        }

        return $this->render('index', [
            'proposedAgenda' => $proposalFactory->create(),
            'expandAll'      => $this->consultation->getSettings()->pProcedureExpandAll,
            'expandId'       => ($expandId ? IntVal($expandId) : null),
        ]);
    }

    /**
     * @param int $agendaItemId
     * @param null|int $expandId
     * @return string
     */
    public function actionIndexAjax($agendaItemId = 0, $expandId = null)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $proposalFactory = new Factory($this->consultation, true, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, true);
        }

        $html = $this->renderPartial('_index_content', [
            'proposedAgenda' => $proposalFactory->create(),
            'expandAll'      => $this->consultation->getSettings()->pProcedureExpandAll,
            'expandId'       => ($expandId ? IntVal($expandId) : null),
        ]);

        return json_encode([
            'success' => true,
            'html'    => $html,
            'date'    => date('H:i:s'),
        ]);
    }

    /**
     * @param int $agendaItemId
     * @param int $comments
     * @param int $onlypublic
     * @return string
     */
    public function actionOds($agendaItemId = 0, $comments = 0, $onlypublic = 0)
    {
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        $comments   = (IntVal($comments) === 1);
        $onlypublic = (IntVal($onlypublic) === 1);

        $filename = 'proposed-procedure';
        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $filename        .= '-' . trim($agendaItem->getShownCode(true), "\t\n\r\0\x0b.");
            $proposalFactory = new Factory($this->consultation, !$onlypublic, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, !$onlypublic);
        }
        if ($onlypublic) {
            $filename .= '-public';
        }
        $filename .= '.ods';

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=' . rawurlencode($filename));
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods', [
            'proposedAgenda' => $proposalFactory->create(),
            'comments'       => $comments,
            'onlyPublic'     => $onlypublic,
        ]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionSaveMotionComment()
    {
        $motionId = \Yii::$app->request->post('id');
        $text     = \Yii::$app->request->post('comment');

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not open motion',
            ]);
        }
        $comment               = new MotionAdminComment();
        $comment->motionId     = $motion->id;
        $comment->text         = $text;
        $comment->userId       = User::getCurrentUser()->getId();
        $comment->status       = MotionAdminComment::PROPOSED_PROCEDURE;
        $comment->dateCreation = date('Y-m-d H:i:s');
        if (!$comment->save()) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not save the comment',
            ]);
        }

        $user = $comment->getMyUser();
        return json_encode([
            'success'  => true,
            'date_str' => Tools::formatMysqlDateTime($comment->dateCreation),
            'text'     => HTMLTools::textToHtmlWithLink($comment->text),
            'user_str' => $user ? $user->name : '-',
        ]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionSaveAmendmentComment()
    {
        $amendmentId = \Yii::$app->request->post('id');
        $text        = \Yii::$app->request->post('comment');

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getAmendment($amendmentId);
        if (!$motion) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not open amendment',
            ]);
        }
        $comment               = new AmendmentAdminComment();
        $comment->amendmentId  = $motion->id;
        $comment->text         = $text;
        $comment->userId       = User::getCurrentUser()->getId();
        $comment->status       = MotionAdminComment::PROPOSED_PROCEDURE;
        $comment->dateCreation = date('Y-m-d H:i:s');
        if (!$comment->save()) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not save the comment',
            ]);
        }

        $user = $comment->getMyUser();
        return json_encode([
            'success'  => true,
            'date_str' => Tools::formatMysqlDateTime($comment->dateCreation),
            'text'     => HTMLTools::textToHtmlWithLink($comment->text),
            'user_str' => $user ? $user->name : '-',
        ]);
    }

    /**
     * @return string
     */
    public function actionSaveMotionVisible()
    {
        $motionId = \Yii::$app->request->post('id');

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not open motion',
            ]);
        }

        if (\Yii::$app->request->post('visible', 0)) {
            $motion->setProposalPublished();
        } else {
            $motion->proposalVisibleFrom = null;
            $motion->save();
        }

        return json_encode([
            'success' => true
        ]);
    }

    /**
     * @return string
     */
    public function actionSaveAmendmentVisible()
    {
        $amendmentId = \Yii::$app->request->post('id');

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not open amendment',
            ]);
        }

        if (\Yii::$app->request->post('visible', 0)) {
            $amendment->setProposalPublished();
        } else {
            $amendment->proposalVisibleFrom = null;
            $amendment->save();
        }

        return json_encode([
            'success' => true
        ]);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return string
     */
    public function actionSaveResponsibility(string $type, string $id)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        /** @var null|IMotion $imotion */
        $imotion = null;
        switch ($type) {
            case 'motion':
                $imotion = $this->consultation->getMotion($id);
                break;
            case 'amendment':
                $imotion = $this->consultation->getAmendment($id);
                break;
        }
        if (!$imotion) {
            return json_encode([
                'success' => false,
                'error'   => 'Could not open amendment',
            ]);
        }

        if (\Yii::$app->request->post('comment') !== null) {
            $imotion->responsibilityComment = \Yii::$app->request->post('comment');
            $imotion->save();
        }
        if (\Yii::$app->request->post('user') !== null) {
            if (\Yii::$app->request->post('user') === '0') {
                $imotion->responsibilityId = null;
            } else {
                $imotion->responsibilityId = intval(\Yii::$app->request->post('user'));
            }
            $imotion->save();
        }

        return json_encode([
            'success' => true
        ]);
    }
}
