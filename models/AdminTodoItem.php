<?php

declare(strict_types=1);

namespace app\models;

use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\components\{HashedStaticCache, MotionSorter, Tools, UrlHelper};
use app\models\db\{Amendment, AmendmentComment, Consultation, IMotion, Motion, MotionComment, repostory\MotionRepository, User};

class AdminTodoItem
{
    public const TARGET_MOTION = 1;
    public const TARGET_AMENDMENT = 2;

    public function __construct(
        public string $todoId,
        public string $title,
        public string $action,
        public string $link,
        public int $timestamp,
        public string $description,
        public ?int $targetType,
        public ?int $targetId,
        public ?string $titlePrefix
    ) {
    }

    private static array $todoCache = [];

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addMissingStatutesItem(Consultation $consultation, array $todo): array
    {
        if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return [];
        }

        foreach ($consultation->motionTypes as $motionType) {
            if (!$motionType->amendmentsOnly) {
                continue;
            }
            if (count($motionType->getAmendableOnlyMotions(true, true)) === 0) {
                $description = \Yii::t('admin', 'todo_statutes_create');
                $todo[] = new AdminTodoItem(
                    'statutesCreate' . $motionType->id,
                    $motionType->titlePlural,
                    '',
                    UrlHelper::createUrl(['/admin/motion-type/type', 'motionTypeId' => $motionType->id]),
                    0,
                    $description,
                    null,
                    null,
                    null,
                );
            }
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningMotionsItems(Consultation $consultation, array $todo): array
    {
        $motions = MotionRepository::getScreeningMotions($consultation);
        foreach ($motions as $motion) {
            if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion))) {
                continue;
            }

            $description = \Yii::t('admin', 'todo_from') . ': ' . $motion->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'motionScreen' . $motion->id,
                $motion->getTitleWithPrefix(),
                str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, \Yii::t('admin', 'todo_motion_screen')),
                UrlHelper::createUrl(['/admin/motion/update', 'motionId' => $motion->id]),
                Tools::dateSql2timestamp($motion->dateCreation),
                $description,
                self::TARGET_MOTION,
                $motion->id,
                $motion->getFormattedTitlePrefix(),
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningAmendmentItems(Consultation $consultation, array $todo): array
    {
        $amendments = Amendment::getScreeningAmendments($consultation);
        foreach ($amendments as $amend) {
            if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::amendment($amend))) {
                continue;
            }

            $description = \Yii::t('admin', 'todo_from') . ': ' . $amend->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'amendmentsScreen' . $amend->id,
                $amend->getTitle(),
                \Yii::t('admin', 'todo_amendment_screen'),
                UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => $amend->id]),
                Tools::dateSql2timestamp($amend->dateCreation),
                $description,
                self::TARGET_AMENDMENT,
                $amend->id,
                $amend->getFormattedTitlePrefix(),
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningMotionComments(Consultation $consultation, array $todo): array
    {
        if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, null)) {
            return [];
        }

        $comments = MotionComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'motionCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->getIMotion()->getTitleWithPrefix(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description,
                self::TARGET_MOTION,
                $comment->motionId,
                $comment->getIMotion()->getFormattedTitlePrefix(),
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningAmendmentComments(Consultation $consultation, array $todo): array
    {
        if (!User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, null)) {
            return [];
        }

        $comments = AmendmentComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'amendmentCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->getIMotion()->getTitle(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description,
                self::TARGET_AMENDMENT,
                $comment->amendmentId,
                $comment->getIMotion()->getFormattedTitlePrefix(),
            );
        }
        return $todo;
    }

    private static function getUnsortedItems(Consultation $consultation): array
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return [];
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $todo = $plugin::getAdminTodoItems($consultation, $user);
            if ($todo !== null) {
                return $todo;
            }
        }

        $todo = [];
        $todo = self::addMissingStatutesItem($consultation, $todo);
        $todo = self::addScreeningMotionsItems($consultation, $todo);
        $todo = self::addScreeningAmendmentItems($consultation, $todo);
        $todo = self::addScreeningMotionComments($consultation, $todo);
        $todo = self::addScreeningAmendmentComments($consultation, $todo);

        return $todo;
    }

    private static function getTodoUsersCache(?Consultation $consultation): HashedStaticCache
    {
        return HashedStaticCache::getInstance('getTodoUsersCache', [$consultation?->id]);
    }

    private static function addUserToTodoCache(?Consultation $consultation, ?User $user): void
    {
        $cache = self::getTodoUsersCache($consultation);
        $users = $cache->getCached(function() { return []; });
        if (!in_array($user?->id, $users)) {
            $users[] = $user?->id;
            $cache->setCache($users);
        }
    }

    private static function getTodoCache(?Consultation $consultation, ?int $userId): HashedStaticCache
    {
        $cache = HashedStaticCache::getInstance('getConsultationTodoCount', [$userId, $consultation?->id]);
        if (AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $cache->setIsSynchronized(true);
        }

        return $cache;
    }

    /**
     * @return AdminTodoItem[]
     */
    public static function getConsultationTodos(?Consultation $consultation, bool $setCache): array
    {
        if (!$consultation) {
            return [];
        }

        if (isset(self::$todoCache[$consultation->id])) {
            return self::$todoCache[$consultation->id];
        }

        $todo = self::getUnsortedItems($consultation);
        usort($todo, function (AdminTodoItem $todo1, AdminTodoItem $todo2): int {
            if ($todo1->titlePrefix === $todo2->titlePrefix) {
                return $todo1->timestamp <=> $todo2->timestamp;
            } elseif ($todo1->titlePrefix === null) {
                return -1;
            } elseif ($todo2->titlePrefix === null) {
                return 1;
            } else {
                return MotionSorter::getSortedMotionsSort($todo1->titlePrefix, $todo2->titlePrefix);
            }
        });

        self::$todoCache[$consultation->id] = $todo;

        if ($setCache) {
            // Only set the cache
            $cache = self::getTodoCache($consultation, User::getCurrentUser()?->id);
            $cache->flushCache();
            $cache->setTimeout(30);
            $cache->getCached(function () use ($todo) {
                return count($todo);
            });
        }

        return $todo;
    }

    public static function getConsultationTodoCount(?Consultation $consultation, bool $onlyIfExists): ?int
    {
        $cache = self::getTodoCache($consultation, User::getCurrentUser()?->id);
        $cache->setTimeout(5 * 60);

        // For large consultations (identified by having a view cache), load the list asynchronously.
        // Downside: a bit shaky layout when loading. For smaller consultations, it's not worth the tradeoff.
        if ($onlyIfExists && AntragsgruenApp::getInstance()->viewCacheFilePath && !$cache->cacheIsFilled()) {
            return null;
        }

        return $cache->getCached(function () use ($consultation) {
            self::addUserToTodoCache($consultation, User::getCurrentUser());
            return count(self::getConsultationTodos($consultation, false));
        });
    }

    public static function flushConsultationTodoCount(?Consultation $consultation): void
    {
        $cache = self::getTodoUsersCache($consultation);
        $users = $cache->getCached(function() { return []; });
        $cache->setCache([]);
        if (!in_array(User::getCurrentUser()?->id, $users)) {
            $users[] = User::getCurrentUser()?->id;
        }
        foreach ($users as $userId) {
            self::flushUserTodoCount($consultation, $userId);
        }
    }

    public static function flushUserTodoCount(?Consultation $consultation, ?int $userId): void
    {
        $cache = self::getTodoCache($consultation, $userId);
        $cache->flushCache();
    }

    /**
     * @param IMotion $IMotion
     *
     * @return AdminTodoItem[]
     */
    public static function getTodosForIMotion(IMotion $IMotion): array
    {
        return array_values(array_filter(
            self::getConsultationTodos($IMotion->getMyConsultation(), true),
            fn(AdminTodoItem $item): bool => $item->isForIMotion($IMotion)
        ));
    }

    public function isForIMotion(IMotion $IMotion): bool
    {
        if (is_a($IMotion, Motion::class) && $this->targetType === self::TARGET_MOTION && $this->targetId === $IMotion->id) {
            return true;
        }
        if (is_a($IMotion, Amendment::class) && $this->targetType === self::TARGET_AMENDMENT && $this->targetId === $IMotion->id) {
            return true;
        }
        return false;
    }
}
