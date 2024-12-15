<?php

namespace app\controllers;

use app\models\AdminTodoItem;
use app\models\exceptions\ResponseException;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\views\consultation\LayoutHelper;
use app\models\http\{BinaryFileResponse,
    HtmlErrorResponse,
    HtmlResponse,
    JsonResponse,
    RedirectResponse,
    ResponseInterface,
    RestApiResponse};
use app\components\{DateTools, RSSExporter, Tools, UrlHelper};
use app\models\db\{Amendment,
    AmendmentComment,
    IComment,
    IRSSItem,
    Consultation,
    MotionComment,
    repostory\MotionRepository,
    SpeechQueue,
    User,
    UserNotification};
use app\models\exceptions\Internal;
use app\models\forms\ConsultationActivityFilterForm;
use app\models\proposedProcedure\Factory;

class ConsultationController extends Base
{
    use ConsultationAgendaTrait;

    public const VIEW_ID_HOME = 'home';
    public const VIEW_ID_INDEX = 'index';

    /**
     * @param \yii\base\Action $action
     *
     * @throws \Exception
     */
    public function beforeAction($action): bool
    {
        if ($action->id === self::VIEW_ID_HOME) {
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                if ($plugin::siteHomeIsAlwaysPublic()) {
                    $this->allowNotLoggedIn = true;
                }
            }
        }

        return parent::beforeAction($action);
    }

    public function actionSearch(): ResponseInterface
    {
        $query = $this->getRequestValue('query');
        if (!$query || trim($query) == '') {
            $this->getHttpSession()->setFlash('error', \Yii::t('con', 'search_no_query'));

            return new RedirectResponse(UrlHelper::createUrl('consultation/index'));
        }

        $results = $this->consultation->fulltextSearch($query, [
            'backTitle' => 'Suche',
            'backUrl'   => UrlHelper::createUrl(['consultation/search', 'query' => $query]),
        ]);

        return new HtmlResponse($this->render(
            'search_results',
            [
                'query'   => $query,
                'results' => $results
            ]
        ));
    }

    public function actionFeeds(int $page = 0): HtmlResponse
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $form = new ConsultationActivityFilterForm($this->consultation);
        $form->setPage(intval($page));

        return new HtmlResponse($this->render('feeds', [
            'admin' => User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null),
        ]));
    }


    public function actionFeedmotions(): BinaryFileResponse
    {
        $newest = MotionRepository::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_motions'));
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedmotions')));
        foreach ($newest as $motion) {
            $motion->addToFeed($feed);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_XML,
            $feed->getFeed(),
            false,
            'feed.xml',
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionFeedamendments(): BinaryFileResponse
    {
        $newest = Amendment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_amendments'));
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedamendments')));
        foreach ($newest as $amend) {
            $amend->addToFeed($feed);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_XML,
            $feed->getFeed(),
            false,
            'feed.xml',
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionFeedcomments(): BinaryFileResponse
    {
        $newest = IComment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl) {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . \Yii::t('con', 'feed_comments'));
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedcomments')));
        foreach ($newest as $comm) {
            $comm->addToFeed($feed);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_XML,
            $feed->getFeed(),
            false,
            'feed.xml',
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionFeedall(): BinaryFileResponse
    {
        $items = array_merge(
            MotionRepository::getNewestByConsultation($this->consultation, 20),
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
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedall')));

        foreach ($items as $item) {
            /** @var IRSSItem $item */
            $item->addToFeed($feed);
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_XML,
            $feed->getFeed(),
            false,
            'feed.xml',
            $this->layoutParams->isRobotsIndex($this->action)
        );
    }

    public function actionNotifications(): HtmlResponse
    {
        $this->forceLogin();

        $user = User::getCurrentUser();
        $con  = $this->consultation;

        if ($this->isPostSet('save')) {
            $newNotis = $this->getPostValue('notifications', []);
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
                    $commentSetting = UserNotification::COMMENT_SETTINGS[0];
                }
                UserNotification::addCommentNotification($user, $con, $commentSetting);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            }
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        $notifications = UserNotification::getUserConsultationNotis($user, $this->consultation);

        return new HtmlResponse($this->render('user_notifications', ['user' => $user, 'notifications' => $notifications]));
    }

    private function consultationSidebar(Consultation $consultation): void
    {
        $newestAmendments = Amendment::getNewestByConsultation($consultation, 5);
        $newestMotions    = MotionRepository::getNewestByConsultation($consultation, 3);
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

    public function actionHome(): ResponseInterface
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::hasSiteHomePage()) {
                return $plugin::getSiteHomePage();
            }
        }

        return $this->actionIndex();
    }

    public function actionMotions(): ResponseInterface
    {
        return new HtmlResponse($this->render('motions', []));
    }

    public function actionResolutions(): ResponseInterface
    {
        return new HtmlResponse($this->render('resolutions', []));
    }

    public function actionIndex(): ResponseInterface
    {
        if (!$this->consultation) {
            return new HtmlErrorResponse(500, 'The page was not found. This might be due to a misconfiguration of the installation.');
        }
        if ($this->consultation->getSettings()->maintenanceMode && !User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return $this->renderContentPage('maintenance');
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $pluginHome = $plugin::getConsultationHomePage($this->consultation);
            if ($pluginHome !== null) {
                return $pluginHome;
            }
        }

        if ($this->consultation->getForcedMotion()) {
            return new RedirectResponse(UrlHelper::createMotionUrl($this->consultation->getForcedMotion()));
        }

        $cache = LayoutHelper::getHomePageCache($this->consultation);
        if ($cache->isSkipCache() || !$cache->cacheIsFilled()) {
            $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);
        }

        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $myself = User::getCurrentUser();

        return new HtmlResponse($this->render('index', [
            'cache' => $cache,
            'consultation' => $this->consultation,
            'myself' => $myself,
            'myMotions' => $myself?->getMySupportedMotionsByConsultation($this->consultation),
            'myAmendments' => $myself?->getMySupportedAmendmentsByConsultation($this->consultation),
            'myMotionComments' => MotionComment::getPrivatelyCommentedByConsultation($myself, $this->consultation),
            'myAmendmentComments' => AmendmentComment::getPrivatelyCommentedByConsultation($myself, $this->consultation),
        ]));
    }

    public function actionActivitylog(string $page = "0", ?string $showAll = null): ResponseInterface
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        $isUserAdmin = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);

        $motion = null;
        $amendment = null;
        $user = null;
        $userGroup = null;

        $form = new ConsultationActivityFilterForm($this->consultation);
        $form->setPage(intval($page));

        if ($this->getHttpRequest()->get('amendmentId')) {
            $amendment = $this->consultation->getAmendment((int)$this->getHttpRequest()->get('amendmentId'));
            if (!$amendment) {
                return new HtmlErrorResponse(404, 'Amendment not found');
            }
            $form->setFilterForAmendmentId($amendment->id);
        } elseif ($this->getHttpRequest()->get('motionId')) {
            $motion = $this->consultation->getMotion((string)$this->getHttpRequest()->get('motionId'));
            if (!$motion) {
                return new HtmlErrorResponse(404, 'Motion not found');
            }
            $form->setFilterForMotionId($motion->id);
        } elseif ($isUserAdmin && $this->getHttpRequest()->get('userId')) {
            $user = User::getCachedUser((int)$this->getHttpRequest()->get('userId'));
            if (!$user) {
                return new HtmlErrorResponse(404, 'User not found');
            }
            $form->setFilterForUserId($user->id);
        } elseif ($isUserAdmin && $this->getHttpRequest()->get('userGroupId')) {
            $userGroup = $this->consultation->getUserGroupById((int)$this->getHttpRequest()->get('userGroupId'), true);
            if (!$userGroup) {
                return new HtmlErrorResponse(404, 'User group not found');
            }
            $form->setFilterForUserGroupId($userGroup->id);
        }

        $showInvisible = false;
        if ($showAll && User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            $form->setShowUserInvisibleEvents(true);
            $showInvisible = true;
        }

        return new HtmlResponse($this->render('activity_log', [
            'form' => $form,
            'motion' => $motion,
            'amendment' => $amendment,
            'user' => $user,
            'userGroup' => $userGroup,
            'showInvisible' => $showInvisible,
        ]));
    }

    public function actionProposedProcedure(): HtmlResponse
    {
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);

        $this->layout = 'column1';

        $proposalFactory = new Factory($this->consultation, false);

        return new HtmlResponse($this->render('proposed_procedure', [
            'proposedAgenda' => $proposalFactory->create(),
        ]));
    }

    public function actionProposedProcedureRest(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);
        $proposalFactory = new Factory($this->consultation, false);

        return new RestApiResponse(200, null, $this->renderPartial('proposed_procedure_rest_get', [
            'proposedAgenda' => $proposalFactory->create(),
        ]));
    }

    public function actionProposedProcedureAjax(): JsonResponse
    {
        $proposalFactory = new Factory($this->consultation, false);

        $html = $this->renderPartial('_proposed_procedure_content', [
            'proposedAgenda' => $proposalFactory->create(),
        ]);

        return new JsonResponse([
            'success' => true,
            'html'    => $html,
            'date'    => date('H:i:s'),
        ]);
    }

    public function actionCollecting(): ResponseInterface
    {
        if (!$this->consultation->getSettings()->collectingPage) {
            return new HtmlErrorResponse(404, 'This site is not available');
        }

        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        return new HtmlResponse($this->render('collecting'));
    }

    public function actionRest(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ONLY_AMENDMENTS);

        return new RestApiResponse(200, null, $this->renderPartial('rest_get', ['consultation' => $this->consultation]));
    }

    public function actionRestSite(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        return new RestApiResponse(200, null, $this->renderPartial('rest_site_get', ['site' => $this->site]));
    }

    public function actionDebugbarAjax(): JsonResponse
    {
        switch ($this->getPostValue('action', '')) {
            case 'close':
                DateTools::setDeadlineDebugMode($this->consultation, false);

                return new JsonResponse(['success' => true]);
            case 'setTime':
                try {
                    $time = Tools::dateBootstraptime2sql($this->getPostValue('time'));
                } catch (Internal $e) {
                    $time = null;
                }
                DateTools::setDeadlineTime($this->consultation, $time);

                return new JsonResponse(['success' => true]);
            default:
                return new JsonResponse(['success' => false, 'error' => 'No operation given']);
        }
    }

    public function actionTodo(): HtmlResponse
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_ANY, PrivilegeQueryContext::anyRestriction())) {
            throw new ResponseException(new HtmlErrorResponse(403, \Yii::t('admin', 'no_access')));
        }

        $todo = AdminTodoItem::getConsultationTodos($this->consultation, true);

        return new HtmlResponse($this->render('todo', ['todo' => $todo]));
    }

    public function actionTodoCount(): JsonResponse
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_ANY, PrivilegeQueryContext::anyRestriction())) {
            throw new ResponseException(new HtmlErrorResponse(403, \Yii::t('admin', 'no_access')));
        }

        $todo = AdminTodoItem::getConsultationTodoCount($this->consultation, false);

        return new JsonResponse(['count' => $todo]);
    }

    private function getUnassignedQueueOrCreate(): SpeechQueue
    {
        foreach ($this->consultation->speechQueues as $queue) {
            if ($queue->motionId === null && $queue->agendaItemId === null) {
                return $queue;
            }
        }

        $unassignedQueue = SpeechQueue::createWithSubqueues($this->consultation, false);
        $unassignedQueue->save();
        return $unassignedQueue;
    }

    public function actionAdminSpeech(?string $queue = null): ResponseInterface
    {
        $this->layout = 'column2';

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::homeUrl());
        }

        $foundQueue = null;
        if ($queue) {
            $queueId = intval($queue);
            foreach ($this->consultation->speechQueues as $speechQueue) {
                if ($speechQueue->id === $queueId) {
                    $foundQueue = $speechQueue;
                }
            }
        } else {
            $foundQueue = $this->getUnassignedQueueOrCreate();
        }
        if (!$foundQueue) {
            return new HtmlErrorResponse(404, 'Speaking list not found');
        }

        return new HtmlResponse($this->render('@app/views/speech/admin-singlepage', ['queue' => $foundQueue]));
    }

    public function actionSpeech(): HtmlResponse
    {
        $this->layout = 'column2';

        $queue = null;
        foreach ($this->consultation->speechQueues as $speechQueue) {
            if ($speechQueue->isActive) {
                $queue = $speechQueue;
            }
        }
        if (!$queue) {
            $queue = $this->getUnassignedQueueOrCreate();
        }
        return new HtmlResponse($this->render('@app/views/speech/index-singlepage', ['queue' => $queue]));
    }

    public function actionAdminVotings(): ResponseInterface
    {
        $this->layout = 'column2';

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_VOTINGS, null)) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_edit_permission'));

            return new RedirectResponse(UrlHelper::homeUrl());
        }


        return new HtmlResponse($this->render('@app/views/voting/admin-votings'));
    }

    public function actionVotings(): ResponseInterface
    {
        $this->forceLogin();
        $this->layout = 'column2';

        return new HtmlResponse($this->render('@app/views/voting/votings'));
    }

    public function actionVotingResults(): ResponseInterface
    {
        $this->forceLogin();
        $this->layout = 'column2';

        return new HtmlResponse($this->render('@app/views/voting/voting-results'));
    }

    private function tagMotionResolutionList(int $tagId, bool $isResolutionList): ResponseInterface
    {
        $tag = $this->consultation->getTagById($tagId);
        if (!$tag) {
            return new HtmlErrorResponse(404, 'Tag not found');
        }

        $myself = User::getCurrentUser();

        return new HtmlResponse($this->render('tag_motion_list', [
            'tag' => $tag,
            'cache' => LayoutHelper::getTagMotionListCache($this->consultation, $tag, $isResolutionList),
            'isResolutionList' => $isResolutionList,
            'myMotionComments' => MotionComment::getPrivatelyCommentedByConsultation($myself, $this->consultation),
            'myAmendmentComments' => AmendmentComment::getPrivatelyCommentedByConsultation($myself, $this->consultation),
        ]));
    }

    public function actionTagsMotions(int $tagId): ResponseInterface
    {
        return $this->tagMotionResolutionList($tagId, false);
    }

    public function actionTagsResolutions(int $tagId): ResponseInterface
    {
        return $this->tagMotionResolutionList($tagId, true);
    }
}
