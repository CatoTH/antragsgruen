<?php

use app\models\policies\IPolicy;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;

$allPolicies = [];
foreach (IPolicy::getPolicyNames() as $id => $name) {
    $allPolicies[] = ["id" => $id, "title" => $name];
}

if (\app\models\db\ConsultationUserGroup::consultationHasLoadableUserGroups($consultation)) {
    $groupLoadUrl = \app\components\UrlHelper::createUrl('/admin/users/search-groups');
} else {
    $groupLoadUrl = '';
}

ob_start();
?>
<div class="v-policy-select">
    <select class="stdDropdown" @change="setPolicy($event)">
        <option v-for="policyIterator in ALL_POLICIES" :value="policyIterator.id" :selected="policy.id === policyIterator.id">{{ policyIterator.title }}</option>
    </select>
    <v-select v-if="policy.id === POLICY_USER_GROUPS"
              @search="onGroupSearch"
              multiple :options="userGroupOptions" :reduce="group => group.id" :value="userGroups"
              @input="setSelectedGroups($event)"></v-select>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    const GROUP_LOAD_URL = <?= json_encode($groupLoadUrl) ?>;

    __setVueComponent('voting', 'component', 'policy-select', {
        template: <?= json_encode($html) ?>,
        props: ['allGroups', 'allowAnonymous', 'policy'],
        data() {
            return {
                POLICY_USER_GROUPS: <?= IPolicy::POLICY_USER_GROUPS ?>,
                ALL_POLICIES: <?= json_encode($allPolicies) ?>,
                changedUserGroups: null,
                ajaxLoadedUserGroups: [],
            }
        },
        computed: {
            userGroupOptions: function () {
                return [
                    ...this.allGroups.map(function(group) {
                        return {
                            label: group.title,
                            id: group.id,
                        }
                    }),
                    ...this.ajaxLoadedUserGroups
                ];
            },
            userGroups: {
                get: function () {
                    if (this.changedUserGroups) {
                        return this.changedUserGroups;
                    } else {
                        return (this.policy.user_groups ? this.policy.user_groups : []);
                    }
                },
                set: function (values) {
                    this.changedUserGroups = values;
                }
            }
        },
        methods: {
            onChange: function() {
                const data = {
                    'id': this.policy.id,
                };
                if (this.policy.id === this.POLICY_USER_GROUPS) {
                    data.user_groups = this.changedUserGroups;
                }
                this.$emit('change', data);
            },
            onGroupSearch(search, loading) {
                if(search.length) {
                    loading(true);

                    fetch(GROUP_LOAD_URL + '?query=' + encodeURIComponent(search)).then(res => {
                        res.json().then(json => {
                            loading(false);
                            this.ajaxLoadedUserGroups = json;
                        });
                    });
                }
            },
            setPolicy: function ($event) {
                this.policy.id = parseInt($event.target.value, 10);
                this.onChange();
            },
            setSelectedGroups: function($event) {
                this.changedUserGroups = $event;
                this.onChange();
            },
        }
    });
</script>
