<?php

declare(strict_types=1);

namespace app\plugins\openslides;

use app\components\Tools;
use app\models\db\{ConsultationUserGroup, Site, User as InternalUser, User};
use app\models\exceptions\Internal;
use app\plugins\openslides\DTO\{AutoupdateUpdate, User as OSUser, Usergroup};
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\{ArrayDenormalizer, ObjectNormalizer};
use Symfony\Component\Serializer\{Serializer, SerializerInterface};

class AutoupdateSyncService
{
    private Site $site;
    private SiteSettings $siteSettings;

    public function setRequestData(Site $site): void
    {
        $this->site = $site;
        /** @var SiteSettings $settings */
        $settings = $this->site->getSettings();
        $this->siteSettings = $settings;
    }

    /**
     * The purpose of this method is to make the parsing of the configured Serializer testable
     */
    public function parseRequest(string $postedJson): AutoupdateUpdate
    {
        return Tools::getSerializer()->deserialize($postedJson, AutoupdateUpdate::class, 'json');
    }

    /**
     * @param Usergroup[] $groups
     */
    public function syncUsergroups(array $groups, bool $full): void
    {
        $authPrefix = $this->siteSettings->getAuthPrefix();

        $foundExternalIds = [];
        foreach ($groups as $osGroup) {
            $externalId = $authPrefix . ':' . $osGroup->getId();
            $internalGroup = ConsultationUserGroup::findByExternalId($externalId);
            if (!$internalGroup) {
                $internalGroup = new ConsultationUserGroup();
                $internalGroup->siteId = $this->site->id;
                $internalGroup->consultationId = null;
                $internalGroup->externalId = $externalId;
                $internalGroup->permissions = '';
                $internalGroup->selectable = 1;
            }
            if ($internalGroup->siteId !== $this->site->id) {
                throw new Internal('usergroup is already assigned to another site: ' . $externalId . ' / ' . $internalGroup->siteId);
            }
            $internalGroup->title = $osGroup->getName();
            $internalGroup->templateId = null;
            $internalGroup->save();
            $foundExternalIds[] = strtolower($externalId);
        }

        if ($full) {
            foreach ($this->site->userGroups as $internalGroup) {
                // Check if this user group was added by this OpenSlides instance
                if ($internalGroup->belongsToExternalAuth($authPrefix) && !in_array(strtolower($internalGroup->externalId), $foundExternalIds)) {
                    // This group was not in the sent payload => delete it
                    foreach ($internalGroup->users as $user) {
                        $internalGroup->unlink('users', $user, true);
                    }
                    $internalGroup->delete();
                }
            }
        }
    }

    public function syncUser(OSUser $osUser): InternalUser {
        $auth    = $this->siteSettings->getAuthPrefix() . ':' . $osUser->getId();
        /** @var InternalUser|null $userObj */
        $userObj = InternalUser::find()->where(['auth' => $auth])->andWhere('status != ' . InternalUser::STATUS_DELETED)->one();
        if (!$userObj) {
            $userObj                  = new InternalUser();
            $userObj->auth            = $auth;
            $userObj->emailConfirmed  = 1;
            $userObj->pwdEnc          = '';
            $userObj->organizationIds = '';
            $userObj->status          = InternalUser::STATUS_CONFIRMED;
        }

        // Set this with every login
        $userObj->name         = $osUser->getUsername();
        $userObj->nameFamily   = $osUser->getLastName();
        $userObj->nameGiven    = $osUser->getFirstName();
        $userObj->organization = $osUser->getStructureLevel();
        $userObj->email        = $osUser->getEmail();
        $userObj->fixedData    = User::FIXED_NAME | User::FIXED_ORGA;
        if (!$userObj->save()) {
            var_dump($userObj->getErrors());
            throw new Internal('Could not create the user');
        }

        $foundOsGroupIds = [];
        $userHasGroupIds = array_map(function(ConsultationUserGroup $group): ?string {
            return ($group->externalId ? strtolower($group->externalId) : null); // also returns irrelevant user groups
        }, $userObj->userGroups);
        foreach ($osUser->getGroupsId() as $osGroupId) {
            $externalId = $this->siteSettings->getAuthPrefix() . ':' . $osGroupId;
            $userGroup = ConsultationUserGroup::findByExternalId($externalId);
            if ($userGroup) {
                $foundOsGroupIds[] = strtolower($userGroup->externalId);
                if (!in_array(strtolower($userGroup->externalId), $userHasGroupIds)) {
                    $userObj->link('userGroups', $userGroup);
                }
            }
        }

        foreach ($userObj->userGroups as $userGroup) {
            if ($userGroup->belongsToExternalAuth($this->siteSettings->getAuthPrefix()) && !in_array(strtolower($userGroup->externalId), $foundOsGroupIds)) {
                $userObj->unlink('userGroups', $userGroup, true);
            }
        }

        return $userObj;
    }

    /**
     * @param OSUser[] $users
     */
    public function syncUsers(array $users, bool $fullSync): void
    {
        $foundExternalIds = [];
        foreach ($users as $osUser) {
            $internalUser = $this->syncUser($osUser);
            $foundExternalIds[] = strtolower($internalUser->auth);
        }

        if ($fullSync) {
            $authFilter = $this->siteSettings->getAuthPrefix() . ':%';
            /** @var InternalUser[] $internalUsers */
            $internalUsers = InternalUser::find()->filterWhere(['like', 'auth', $authFilter, false])->andWhere('status != ' . InternalUser::STATUS_DELETED)->all();
            foreach ($internalUsers as $internalUser) {
                if (!in_array(strtolower($internalUser->auth), $foundExternalIds)) {
                    $internalUser->deleteAccount();
                }
            }
        }
    }
}
