<?php

namespace app\controllers;

use app\components\{DateTools, RSSExporter, Tools, UrlHelper};
use app\models\db\{Amendment, AmendmentComment, IComment, IRSSItem, Motion, Consultation, MotionComment, SpeechQueue, User, UserNotification};
use app\models\exceptions\Internal;
use app\models\forms\ConsultationActivityFilterForm;
use app\models\proposedProcedure\Factory;
use yii\web\Response;

class ConsultationController extends Base
{
    use ConsultationAgendaTrait;

    /**
     * @param \yii\base\Action $action
     *
     * @return bool
     * @throws \Exception
     * @throws Internal
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
     * @throws Internal
     * @throws \yii\base\ExitException
     */
    public function actionSearch()
    {
        $query = $this->getRequestValue('query');
        if (!$query || trim($query) == '') {
            \Yii::$app->session->setFlash('error', \Yii::t('con', 'search_no_query'));

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
     * @param int $page
     *
     * @return string
     */
    public function actionFeeds($page = 0)
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $form = new ConsultationActivityFilterForm($this->consultation);
        $form->setPage(intval($page));

        return $this->render('feeds', [
            'admin' => User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT),
        ]);
    }


    /**
     * @return string
     * @throws Internal
     */
    public function actionFeedmotions()
    {
        $newest = Motion::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_motions'));
        $feed->setLanguage(\Yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedmotions')));
        foreach ($newest as $motion) {
            $motion->addToFeed($feed);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/xml');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $feed->getFeed();
    }

    /**
     * @return string
     * @throws Internal
     */
    public function actionFeedamendments()
    {
        $newest = Amendment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_amendments'));
        $feed->setLanguage(\Yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedamendments')));
        foreach ($newest as $amend) {
            $amend->addToFeed($feed);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/xml');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $feed->getFeed();
    }

    /**
     * @return string
     */
    public function actionFeedcomments()
    {
        $newest = IComment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_comments'));
        $feed->setLanguage(\Yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedcomments')));
        foreach ($newest as $comm) {
            $comm->addToFeed($feed);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/xml');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $feed->getFeed();
    }

    /**
     * @return string
     */
    public function actionFeedall()
    {
        $items = array_merge(
            Motion::getNewestByConsultation($this->consultation, 20),
            Amendment::getNewestByConsultation($this->consultation, 20),
            MotionComment::getNewestByConsultation($this->consultation, 20),
            AmendmentComment::getNewestByConsultation($this->consultation, 20)
        );
        usort($items, function (IRSSItem $item1, IRSSItem $item2) {
            return -1 * Tools::compareSqlTimes($item1->getDate(), $item2->getDate());
        });
        $items = array_slice($items, 0, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_all'));
        $feed->setLanguage(\Yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedall')));

        foreach ($items as $item) {
            /** @var IRSSItem $item */
            $item->addToFeed($feed);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/xml');
        if (!$this->layoutParams->isRobotsIndex($this->action)) {
            \Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $feed->getFeed();
    }

    public function actionNotifications()
    {
        $this->forceLogin();

        $user = User::getCurrentUser();
        $con  = $this->consultation;

        if ($this->isPostSet('save')) {
            $newNotis = \Yii::$app->request->post('notifications', []);
            if (isset($newNotis['motion'])) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            }

            if (isset($newNotis['amendment'])) {
                if (isset($newNotis['amendmentsettings']) && $newNotis['amendmentsettings'] == 1) {
                    UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
                } else {
                    UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION);
                }
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_AMENDMENT_MY_MOTION);
            }

            if (isset($newNotis['comment'])) {
                if (isset($newNotis['commentsetting'])) {
                    $commentSetting = IntVal($newNotis['commentsetting']);
                } else {
                    $commentSetting = UserNotification::$COMMENT_SETTINGS[0];
                }
                UserNotification::addCommentNotification($user, $con, $commentSetting);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            }
            \Yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        $notifications = UserNotification::getUserConsultationNotis($user, $this->consultation);

        return $this->render('user_notifications', ['user' => $user, 'notifications' => $notifications]);
    }

    private function consultationSidebar(Consultation $consultation): void
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
        $behaviorHome = $this->site->getBehaviorClass()->getConsultationHomePage($this->consultation);
        if ($behaviorHome !== null) {
            return $behaviorHome;
        }

        if ($this->consultation->getForcedMotion()) {
            $this->redirect(UrlHelper::createMotionUrl($this->consultation->getForcedMotion()));
        }

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);

        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

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
            ]
        );
    }

    public function actionActivitylog(string $page = "0", ?string $showAll = null, ?string $amendmentId = null, ?string $motionId = null): string
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $motion = null;
        $amendment = null;

        $form = new ConsultationActivityFilterForm($this->consultation);
        $form->setPage(intval($page));

        if ($amendmentId) {
            $amendment = $this->consultation->getAmendment($amendmentId);
            if (!$amendment) {
                return $this->showErrorpage(404, 'Amendment not found');
            }
            $form->setFilterForAmendmentId(intval($amendmentId));
        } elseif ($motionId) {
            $motion = $this->consultation->getMotion($motionId);
            if (!$motion) {
                return $this->showErrorpage(404, 'Motion not found');
            }
            $form->setFilterForMotionId(intval($motionId));
        }

        $showInvisible = false;
        if ($showAll && User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            $form->setShowUserInvisibleEvents(true);
            $showInvisible = true;
        }

        return $this->render('activity_log', [
            'form' => $form,
            'motion' => $motion,
            'amendment' => $amendment,
            'showInvisible' => $showInvisible,
        ]);
    }

    /**
     * @return string
     */
    public function actionProposedProcedure()
    {
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);

        $this->layout = 'column1';

        $proposalFactory = new Factory($this->consultation, false);

        return $this->render('proposed_procedure', [
            'proposedAgenda' => $proposalFactory->create(),
        ]);
    }

    /**
     * @return string
     */
    public function actionProposedProcedureRest()
    {
        $this->handleRestHeaders(['GET']);

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);
        $proposalFactory = new Factory($this->consultation, false);

        return $this->returnRestResponse(200, $this->renderPartial('proposed_procedure_rest_get', [
            'proposedAgenda' => $proposalFactory->create(),
        ]));
    }

    /**
     * @return string
     */
    public function actionProposedProcedureAjax()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $proposalFactory = new Factory($this->consultation, false);

        $html = $this->renderPartial('_proposed_procedure_content', [
            'proposedAgenda' => $proposalFactory->create(),
        ]);

        return json_encode([
            'success' => true,
            'html'    => $html,
            'date'    => date('H:i:s'),
        ]);
    }

    public function actionCollecting()
    {
        if (!$this->consultation->getSettings()->collectingPage) {
            return $this->showErrorpage(404, 'This site is not available');
        }

        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        return $this->render('collecting');
    }

    /**
     * @return string
     */
    public function actionRest()
    {
        $this->handleRestHeaders(['GET']);

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);

        return $this->returnRestResponse(200, $this->renderPartial('rest_get', ['consultation' => $this->consultation]));
    }

    /**
     * @return string
     */
    public function actionRestSite()
    {
        $this->handleRestHeaders(['GET']);

        return $this->returnRestResponse(200, $this->renderPartial('rest_site_get', ['site' => $this->site]));
    }

    public function actionDebugbarAjax(): string
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        switch (\Yii::$app->request->post('action')) {
            case 'close':
                DateTools::setDeadlineDebugMode($this->consultation, false);

                return json_encode(['success' => true]);
            case 'setTime':
                try {
                    $time = Tools::dateBootstraptime2sql(\Yii::$app->request->post('time'));
                } catch (Internal $e) {
                    $time = null;
                }
                DateTools::setDeadlineTime($this->consultation, $time);

                return json_encode(['success' => true]);
            default:
                return json_encode(['success' => false, 'error' => 'No operation given']);
        }
    }

    public function actionAdminSpeech(): string
    {
        $this->layout = 'column2';

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            \Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $unassignedQueue = null;
        foreach ($this->consultation->speechQueues as $queue) {
            if ($unassignedQueue === null && $queue->motionId === null && $queue->agendaItemId === null) {
                $unassignedQueue = $queue;
            }
        }
        if ($unassignedQueue === null) {
            $unassignedQueue = SpeechQueue::createWithSubqueues($this->consultation, false);
            $unassignedQueue->save();
        }

        return $this->render('@app/views/speech/admin-singlepage', ['queue' => $unassignedQueue]);
    }
}
