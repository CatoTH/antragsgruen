<?php

namespace app\controllers;

use app\components\MessageSource;
use app\components\ProposedProcedureFactory;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\ConsultationAgendaItem;
use app\models\db\IComment;
use app\models\db\IRSSItem;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\exceptions\Access;
use app\models\exceptions\FormError;
use app\models\forms\ConsultationActivityFilterForm;
use app\models\settings\AntragsgruenApp;
use yii\web\Response;

class ConsultationController extends Base
{
    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \Exception
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $return = parent::beforeAction($action);
        if (!$this->consultation) {
            $this->consultationNotFound();
            return false;
        }
        return $return;
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     */
    public function actionSearch()
    {
        $query = $this->getRequestValue('query');
        if (!$query || trim($query) == '') {
            \yii::$app->session->setFlash('error', \Yii::t('con', 'search_no_query'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $results = $this->consultation->fulltextSearch($query, [
            'backTitle' => 'Suche',
            'backUrl'   => UrlHelper::createUrl(['consultation/search', 'query' => $query]),
        ]);

        return $this->render(
            'search_results',
            [
                'query'   => $query,
                'results' => $results
            ]
        );
    }


    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionFeedmotions()
    {
        $newest = Motion::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_motions'));
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedmotions')));
        foreach ($newest as $motion) {
            $motion->addToFeed($feed);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionFeedamendments()
    {
        $newest = Amendment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_amendments'));
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedamendments')));
        foreach ($newest as $amend) {
            $amend->addToFeed($feed);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     * @return string
     */
    public function actionFeedcomments()
    {
        $newest = IComment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_comments'));
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedcomments')));
        foreach ($newest as $comm) {
            $comm->addToFeed($feed);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     * @return string
     */
    public function actionFeedall()
    {
        /** @var IRSSItem[] $comments */
        $items = array_merge(
            Motion::getNewestByConsultation($this->consultation, 20),
            Amendment::getNewestByConsultation($this->consultation, 20),
            MotionComment::getNewestByConsultation($this->consultation, 20),
            AmendmentComment::getNewestByConsultation($this->consultation, 20)
        );
        usort($items, function ($item1, $item2) {
            /** @var IRSSItem $item1 */
            /** @var IRSSItem $item2 */
            $ts1 = Tools::dateSql2timestamp($item1->getDate());
            $ts2 = Tools::dateSql2timestamp($item2->getDate());
            if ($ts1 < $ts2) {
                return 1;
            }
            if ($ts1 > $ts2) {
                return -1;
            }
            return 0;
        });
        $items = array_slice($items, 0, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_all'));
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedall')));

        foreach ($items as $item) {
            /** @var IRSSItem $item */
            $item->addToFeed($feed);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     *
     */
    public function actionNotifications()
    {
        $this->forceLogin();

        $user = User::getCurrentUser();
        $con  = $this->consultation;

        if ($this->isPostSet('save')) {
            $newNotis = \Yii::$app->request->post('notifications', []);
            if (in_array('motion', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            }
            if (in_array('amendment', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
            }
            if (in_array('comment', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            }
            if (in_array('amendmentMyMotion', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION);
            }
            \Yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        $notifications = UserNotification::getUserConsultationNotis($user, $this->consultation);

        return $this->render('user_notifications', ['user' => $user, 'notifications' => $notifications]);
    }



    /**
     * @param Consultation $consultation
     */
    private function consultationSidebar(Consultation $consultation)
    {
        $newestAmendments = Amendment::getNewestByConsultation($consultation, 5);
        $newestMotions    = Motion::getNewestByConsultation($consultation, 3);
        $newestComments   = IComment::getNewestByConsultation($consultation, 3);

        $this->renderPartial(
            $consultation->getSettings()->getConsultationSidebar(),
            [
                'newestMotions'    => $newestMotions,
                'newestAmendments' => $newestAmendments,
                'newestComments'   => $newestComments,
            ]
        );
    }


    /**
     * @param array $arr
     * @param int|null $parentId
     * @return int[]
     * @throws FormError
     */
    private function saveAgendaArr($arr, $parentId)
    {
        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                $consultationId = IntVal($this->consultation->id);
                $condition      = ['id' => IntVal($jsitem['id']), 'consultationId' => $consultationId];
                /** @var ConsultationAgendaItem $item */
                $item = ConsultationAgendaItem::findOne($condition);
                if (!$item) {
                    throw new FormError('Inconsistency - did not find given agenda item: ' . $condition);
                }
            } else {
                $item                 = new ConsultationAgendaItem();
                $item->consultationId = $this->consultation->id;
            }

            $item->code         = $jsitem['code'];
            $item->title        = $jsitem['title'];
            $item->motionTypeId = ($jsitem['motionTypeId'] > 0 ? $jsitem['motionTypeId'] : null);
            $item->parentItemId = $parentId;
            $item->position     = $i;

            $item->save();
            $items[] = $item->id;

            $items = array_merge($items, $this->saveAgendaArr($jsitem['children'], $item->id));
        }
        return $items;
    }

    /**
     */
    private function saveAgenda()
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            \Yii::$app->session->setFlash('error', 'No permissions to edit this page');
            return;
        }

        $data = json_decode(\Yii::$app->request->post('data'), true);
        if (!is_array($data)) {
            \Yii::$app->session->setFlash('error', 'Could not parse input');
            return;
        }

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            return;
        }

        foreach ($this->consultation->agendaItems as $item) {
            if (!in_array($item->id, $usedItems)) {
                $item->delete();
            }
        }

        $this->consultation->flushCacheWithChildren();
        $this->consultation->refresh();

        \Yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionHome()
    {
        if ($this->site->getBehaviorClass()->hasSiteHomePage()) {
            return $this->site->getBehaviorClass()->getSiteHomePage();
        } else {
            return $this->actionIndex();
        }
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionIndex()
    {
        if ($this->consultation->getForcedMotion()) {
            $this->redirect(UrlHelper::createMotionUrl($this->consultation->getForcedMotion()));
        }

        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        if (isset(\Yii::$app->request->post()['saveAgenda'])) {
            $this->saveAgenda();
        }


        $myself = User::getCurrentUser();
        if ($myself) {
            $myMotions    = $myself->getMySupportedMotionsByConsultation($this->consultation);
            $myAmendments = $myself->getMySupportedAmendmentsByConsultation($this->consultation);
        } else {
            $myMotions    = null;
            $myAmendments = null;
        }

        return $this->render(
            'index',
            [
                'consultation' => $this->consultation,
                'myself'       => $myself,
                'myMotions'    => $myMotions,
                'myAmendments' => $myAmendments,
                'admin'        => User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT),
            ]
        );
    }

    /**
     * @param int $page
     *
     * @return string
     */
    public function actionActivitylog($page = 0)
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $form = new ConsultationActivityFilterForm($this->consultation);
        $form->setPage($page);
        return $this->render('activity_log', ['form' => $form]);
    }

    /**
     * @return string
     */
    public function actionProposedProcedure()
    {
        $this->layout = 'column1';
        $this->consultationSidebar($this->consultation);

        $proposalFactory = new ProposedProcedureFactory($this->consultation, false);

        return $this->render('proposed_procedure', [
            'proposedAgenda' => $proposalFactory->create(),
        ]);
    }

    /**
     * @return string
     */
    public function actionProposedProcedureAjax()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $proposalFactory = new ProposedProcedureFactory($this->consultation, false);

        $html = $this->renderPartial('_proposed_procedure_content', [
            'proposedAgenda' => $proposalFactory->create(),
        ]);

        return json_encode([
            'success' => true,
            'html'    => $html,
            'date'    => date('H:i:s'),
        ]);
    }
}
