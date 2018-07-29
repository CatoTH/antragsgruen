<?php

namespace app\async\models;

use app\models\db\ConsultationUserPrivilege;

class UserPrivilege extends TransferrableObject
{
    const PRIVILEGE_ANY                       = 0;
    const PRIVILEGE_CONSULTATION_SETTINGS     = 1;
    const PRIVILEGE_CONTENT_EDIT              = 2;
    const PRIVILEGE_SCREENING                 = 3;
    const PRIVILEGE_MOTION_EDIT               = 4;
    const PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS = 5;
    const PRIVILEGE_SITE_ADMIN                = 6;
    const PRIVILEGE_CHANGE_PROPOSALS          = 7;

    public $privilegeView;
    public $privilegeCreate;
    public $adminSuper;
    public $adminContentEdit;
    public $adminScreen;
    public $adminProposals;

    /**
     * @param ConsultationUserPrivilege $privilege
     * @return UserPrivilege
     * @throws \Exception
     */
    public static function createFromDbObject(ConsultationUserPrivilege $privilege)
    {
        $object                   = new UserPrivilege('');
        $object->privilegeView    = $privilege->privilegeView;
        $object->privilegeCreate  = $privilege->privilegeCreate;
        $object->adminSuper       = $privilege->adminSuper;
        $object->adminContentEdit = $privilege->adminContentEdit;
        $object->adminScreen      = $privilege->adminScreen;
        $object->adminProposals   = $privilege->adminProposals;
        return $object;
    }

    /**
     * @param int $permission
     * @return boolean
     */
    public function containsPrivilege($permission)
    {
        switch ($permission) {
            case UserPrivilege::PRIVILEGE_ANY:
                return (
                    $this->adminSuper === 1 || $this->adminContentEdit === 1 ||
                    $this->adminScreen || $this->adminProposals
                );
            case UserPrivilege::PRIVILEGE_CONSULTATION_SETTINGS:
                return ($this->adminSuper === 1);
            case UserPrivilege::PRIVILEGE_CONTENT_EDIT:
                return ($this->adminContentEdit === 1);
            case UserPrivilege::PRIVILEGE_SCREENING:
                return ($this->adminScreen === 1);
            case UserPrivilege::PRIVILEGE_CHANGE_PROPOSALS:
                return ($this->adminProposals === 1);
            case UserPrivilege::PRIVILEGE_MOTION_EDIT:
                return ($this->adminSuper === 1);
            case UserPrivilege::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS:
                return ($this->adminSuper === 1);
            default:
                return false;
        }
    }
}
