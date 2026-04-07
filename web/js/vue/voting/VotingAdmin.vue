<template>
  <section class="voting" :class="['voting' + voting.id]" :id="'voting' + voting.id"
           v-t:aria-label="['voting', 'admin_aria_single', false, {'%TITLE%': voting.title}]">
    <h2 class="green">
      {{ voting.title }}
      <span class="btn-group btn-group-xs settingsToggleGroup">
            <button class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false"
                    v-t:title="['voting', 'admin_settings_open']" @click="openSettings()" v-if="!settingsOpened">
                <span class="sr-only" v-t="['voting', 'admin_settings_open']"></span>
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </button>
            <button class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false" v-if="settingsOpened"
                    v-t:title="['voting', 'admin_settings_close']" @click="closeSettings()">
                <span class="sr-only" v-t="['voting', 'admin_settings_close']"></span>
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </button>
        </span>

      <label class="activateHeader">
        <input type="checkbox" v-model="isUsed">
        <template v-t="['voting', 'admin_voting_use']"></template>
        <span class="glyphicon glyphicon-info-sign"
              v-t:aria-label="['voting', 'admin_voting_use_h']"
              v-tooltip="['voting', 'admin_voting_use_h']"
        ></span>
      </label>
    </h2>
    <div class="content votingShow" v-if="!settingsOpened">

      <div class="votingSettingsSummary">
        <div class="majorityType" v-if="getVotingMajority(voting)">
          <strong v-t="['voting', 'settings_majoritytype']"></strong>:
          {{ getVotingMajority(voting).name }}
          <span class="glyphicon glyphicon-info-sign" :aria-label="getVotingMajority(voting).description" v-tooltip="getVotingMajority(voting).description"></span>
        </div>
        <div class="quorumType" v-if="getVotingQuorum(voting)">
          <strong v-t="['voting', 'settings_quorumtype']"></strong>:
          {{ getVotingQuorum(voting).name }}
          ({{ quorumIndicator }})
          <span class="glyphicon glyphicon-info-sign" :aria-label="getVotingQuorum(voting).description"
                v-tooltip="getVotingQuorum(voting).description" v-if="getVotingQuorum(voting).description !== ''"></span>
        </div>
        <div class="votingPolicy">
          <strong v-t="['voting', 'settings_votepolicy']"></strong>:
          {{ voting.vote_policy.description }}
        </div>
        <div class="votingVisibility">
          <strong v-t="['voting', 'settings_votespublic']"></strong>:
          <span v-if="voting.votes_public === 0" v-t="['voting', 'settings_votespublic_nobody']"></span>
          <span v-if="voting.votes_public === 1" v-t="['voting', 'settings_votespublic_admins']"></span>
          <span v-if="voting.votes_public === 2" v-t="['voting', 'settings_votespublic_all']"></span>
        </div>
      </div>
      <div class="alert alert-success" v-if="isOpen">
        <p v-t="['voting', 'admin_status_opened', true]"></p>
      </div>
      <div class="alert alert-info" v-if="voting.status === STATUS_CLOSED_PUBLISHED">
        <p v-t="['voting', 'admin_status_closed', true]"></p>
      </div>
      <div class="alert alert-info" v-if="voting.status === STATUS_CLOSED_UNPUBLISHED">
        <p v-t="['voting', 'admin_status_closed_unpublished', true]"></p>
      </div>
      <form method="POST" class="votingDataActions" v-if="isPreparing">
        <div class="actions">
          <button type="button" class="btn btn-primary btnOpen" @click="openVoting()" v-t="['voting', 'admin_btn_open']"></button>
        </div>
      </form>
      <form method="POST" class="votingDataActions" v-if="isOpen || isClosed">
        <div class="actions" v-if="isOpen">
                <span class="remainingTime" v-if="isOpen && hasVotingTime && remainingVotingTime !== null">
                    <template v-t="['voting', 'remaining_time']"></template>:
                    <span v-if="remainingVotingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
                    <span v-if="remainingVotingTime < 0" class="over" v-t="['speech', 'remaining_time_over']"></span>
                </span>

          <button type="button" class="btn btn-default btnReset" @click="resetVoting()" v-t="['voting', 'admin_btn_reset']"></button>

          <div class="btn-group">
            <button type="button" class="btn btn-primary btnClose" @click="closeVoting(true, $event)" v-t="['voting', 'admin_btn_close']"></button>
            <button type="button" class="btn btn-primary dropdown-toggle btnClosePubOpener" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="caret"></span>
              <span class="sr-only" v-t="['voting', 'admin_btn_close_op']"></span>
            </button>
            <ul class="dropdown-menu">
              <li><a href="#" @click="closeVoting(true, $event)" v-t="['voting', 'admin_btn_close_pub']"></a></li>
              <li><a href="#" @click="closeVoting(false, $event)" class="btnCloseNopub" v-t="['voting', 'admin_btn_close_nopub']"></a></li>
            </ul>
          </div>
        </div>
        <div class="actions" v-if="isClosed">
          <button type="button" class="btn btn-default btnReset" @click="resetVoting()" v-t="['voting', 'admin_btn_reset']"></button>
          <button type="button" class="btn btn-default btnReopen" @click="reopenVoting()" v-t="['voting', 'admin_btn_reopen']"></button>
          <button type="button" class="btn btn-primary btnPublish" @click="publishVoting()"
                  v-if="voting.status === STATUS_CLOSED_UNPUBLISHED" v-t="['voting', 'admin_btn_publish']"></button>
        </div>
      </form>
      <div v-if="groupedVotings.length === 0" class="noVotingsYet">
        <div class="alert alert-info"><p v-t="['voting', 'admin_no_items_yet']"></p></div>
      </div>
      <ul class="votingListAdmin votingListCommon" v-if="groupedVotings.length > 0">
        <li v-if="voting.abstentions_total > 0" class="abstentions"><div>{{ abstentionsStr }}</div></li>
        <template v-for="groupedVoting in groupedVotings">
          <li :class="[
                'voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id,
                'answer_template_' + answerTemplate,
                (isClosed ? 'showResults' : ''),
                (isClosed ? 'showDetailedResults' : ''),
                (isVoteListShown(groupedVoting) ? 'voteListShown' : '')
            ]">
            <div class="titleLink" :class="{'question': voting.answers.length === 1}">
              <div v-if="groupedVoting[0].item_group_name" class="titleGroupName">
                {{ groupedVoting[0].item_group_name }}
              </div>
              <div v-for="item in groupedVoting">
                {{ item.title_with_prefix }}
                <a v-if="item.url_html" :href="item.url_html" v-t:title="['voting', 'voting_show_amend']"><span
                    class="glyphicon glyphicon-new-window" v-t:aria-label="['voting', 'voting_show_amend']"></span></a>
                <a v-if="itemAdminUrl(item)" :href="itemAdminUrl(item)" v-t:title="['voting', 'voting_edit_amend']"
                   :class="'adminUrl' + item.id"><span class="glyphicon glyphicon-wrench" v-t:aria-label="['voting', 'voting_edit_amend']"></span></a>
                <br>
                <span class="amendmentBy" v-if="item.initiators_html" v-t="['voting', 'voting_by', true, {'%BY%': item.initiators_html}]"></span>
              </div>
              <div v-if="votingHasQuorum" class="quorumCounter">
                {{ quorumCounter(groupedVoting) }}
              </div>
              <button v-if="hasVoteList(groupedVoting) && !isVoteListShown(groupedVoting)" @click="showVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                <span class="glyphicon glyphicon-chevron-down" aria-label="true"></span>
                <template v-t="['voting', 'voting_show_votes']"></template>
              </button>
              <button v-if="hasVoteList(groupedVoting) && isVoteListShown(groupedVoting)" @click="hideVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                <span class="glyphicon glyphicon-chevron-up" aria-label="true"></span>
                <template v-t="['voting', 'voting_hide_votes']"></template>
              </button>
            </div>
            <div class="prepActions" v-if="isPreparing">
              <button class="btn btn-link btn-xs removeBtn" type="button" @click="removeItem(groupedVoting)"
                      v-t:title="['voting', 'admin_btn_remove_item']" v-t:aria-label="['voting', 'admin_btn_remove_item']">
                <span class="glyphicon glyphicon-minus-sign" aria-hidden="true"></span>
              </button>
            </div>
            <div class="votesDetailed" v-if="isOpen || isClosed">
              <div v-if="groupedVoting[0].vote_results && groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
                <table class="votingTable votingTableSingle">
                  <thead>
                  <tr>
                    <th v-for="answer in voting.answers">{{ answer.title }}</th>
                    <th v-if="voting.answers.length > 1" v-t="['voting', 'admin_votes_total']"></th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
                      {{ groupedVoting[0].vote_results[0][answer.api_id] }}
                    </td>
                    <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                      {{ getAbsoluteNumberOfVotes(groupedVoting[0].vote_results[0]) }}
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="result" v-if="isClosed && (votingHasMajority || votingHasQuorum)">
              <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                <template v-t="['voting', 'status_accepted']"></template>
              </div>
              <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                <template v-t="['voting', 'status_rejected']"></template>
              </div>
              <div class="accepted" v-if="itemIsQuorumReached(groupedVoting)">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                <template v-t="['voting', 'status_quorum_reached']"></template>
              </div>
              <div class="rejected" v-if="itemIsQuorumFailed(groupedVoting)">
                <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                <template v-t="['voting', 'status_quorum_missed']"></template>
              </div>
            </div>
          </li>
          <li class="voteResults" v-if="isVoteListShown(groupedVoting)">
            <vote-list :voting="voting" :groupedVoting="groupedVoting" :showNotVotedList="true"
                              :setToUserGroupSelection="userGroups" @set-user-group="setVotersToUserGroup"></vote-list>
          </li>
        </template>
      </ul>
      <div v-if="isPreparing" class="addingItemsForm">
        <button class="btn btn-link btn-xs addIMotions" type="button" v-if="!addingMotions && !addingQuestions" @click="addingMotions = true">
          <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
          <template v-t="['voting', 'admin_add_amendments']"></template>
        </button>
        <button class="btn btn-link btn-xs addQuestions" type="button" v-if="!addingMotions && !addingQuestions" @click="openQuestionAdder()">
          <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
          <template v-t="['voting', 'admin_add_question']"></template>
        </button>
        <button class="btn btn-link btn-xs btnRemove" type="button" v-if="addingMotions || addingQuestions" @click="addingQuestions = false; addingMotions = false"
                v-t:aria-label="['voting', 'admin_add_abort']" v-t:title="['voting', 'admin_add_abort']">
          <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
        </button>
        <form v-if="addingMotions" class="addingMotions" @submit="addIMotion($event)">
          <select class="stdDropdown" v-model="addableMotionSelected"
                  v-t:aria-label="['voting', 'admin_add_amendments']" v-t:title="['voting', 'admin_add_amendments']">
            <option value="" v-t="['voting', 'admin_add_amendments_opt']"></option>
            <template v-for="item in addableMotions">
              <!-- Statute amendment -->
              <option v-if="item.type === 'amendment'" :value="'amendment-' + item.id" :disabled="!isAddable(item)">{{ item.title }}</option>

              <option v-if="item.type === 'motion' && item.amendments.length === 0" :value="'motion-' + item.id" :disabled="!isAddable(item)">{{ item.title }}</option>

              <optgroup v-if="item.type === 'motion' && item.amendments.length > 0" :label="item.title">
                <option :value="'motion-' + item.id" :disabled="!isAddable(item)" v-t="['voting', 'admin_add_opt_motion']"></option>
                <option :value="'motion-' + item.id + '-amendments'" v-if="item.amendments.length > 1" v-t="['voting', 'admin_add_opt_all_amend']"></option>
                <option v-for="amendment in item.amendments" :value="'amendment-' + amendment.id" :disabled="!isAddable(amendment)">{{ amendment.title }}</option>
              </optgroup>
            </template>
          </select>
          <button type="submit" :disabled="!addableMotionSelected" class="btn btn-default">
            <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
            <span class="sr-only" v-t="['voting', 'admin_add_btn']"></span>
          </button>
        </form>
        <form v-if="addingQuestions" class="addingQuestions" @submit="addQuestion($event)">
          <label>
            <template v-t="['voting', 'admin_add_question_title']"></template>:
            <input type="text" class="form-control" v-model="addingQuestionText" :id="'voting_question_' + voting.id">
          </label>
          <button type="submit" :disabled="!addingQuestionText" class="btn btn-default">
            <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
            <span class="sr-only" v-t="['voting', 'admin_add_btn']"></span>
          </button>
        </form>
      </div>
      <footer class="votingFooter" v-if="voting.log.length > 2" v-t:aria-label="['voting', 'activity_title']">
        <div class="downloadResults" v-if="isClosed">
          <a class="btn btn-xs btn-link" :href="resultDownloadLink">
            <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
            <template v-t="['voting', 'results_download']"></template>
          </a>
        </div>
        <div class="activityOpener" v-if="activityClosed">
          <button type="button" class="btn btn-link btn-xs" @click="openActivities()">
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
            <template v-t="['voting', 'activity_show_all']"></template>
          </button>
        </div>
        <div class="activityCloser" v-if="!activityClosed">
          <button type="button" class="btn btn-link btn-xs" @click="closeActivities()">
            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
            <template v-t="['voting', 'activity_show_all']"></template>
          </button>
        </div>
        <ol class="activityLog" :class="{ closed: activityClosed }">
          <li v-for="logEntry in voting.log" v-html="formatLogEntry(logEntry)"></li>
        </ol>
      </footer>
      <footer class="votingFooter" v-if="voting.log.length > 0 && voting.log.length <= 2" v-t:aria-label="['voting', 'activity_title']">
        <div class="downloadResults" v-if="isClosed">
          <a class="btn btn-xs btn-link" :href="resultDownloadLink">
            <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
            <template v-t="['voting', 'results_download']"></template>
          </a>
        </div>
        <ol class="activityLog">
          <li v-for="logEntry in voting.log" v-html="formatLogEntry(logEntry)"></li>
        </ol>
      </footer>
    </div>
    <form method="POST" class="content votingSettings" v-if="settingsOpened" @submit="saveSettings($event)">
      <label class="titleSetting">
        <template v-t="['voting', 'settings_title']"></template>:<br>
        <input type="text" v-model="settingsTitle" class="form-control">
      </label>
      <fieldset class="answerTemplate">
        <legend><template v-t="['voting', 'settings_answers']"></template>:</legend>
        <label>
          <input type="radio" :value="ANSWER_TEMPLATE_YES_NO_ABSTENTION" v-model="answerTemplate" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_answers_yesnoabst']"></template>
        </label>
        <label>
          <input type="radio" :value="ANSWER_TEMPLATE_YES_NO" v-model="answerTemplate" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_answers_yesno']"></template>
        </label>
        <label>
          <input type="radio" :value="ANSWER_TEMPLATE_YES" v-model="answerTemplate" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_answers_yes']"></template>
          <span class="glyphicon glyphicon-info-sign"
                v-t:aria-label="['voting', 'settings_answers_yesh']"
                v-tooltip="['voting', 'settings_answers_yesh']"></span>
        </label>
        <label>
          <input type="radio" :value="ANSWER_TEMPLATE_PRESENT" v-model="answerTemplate" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_answers_present']"></template>
          <span class="glyphicon glyphicon-info-sign"
                v-t:aria-label="['voting', 'settings_answers_presenth']"
                v-tooltip="['voting', 'settings_answers_presenth']"></span>
        </label>
      </fieldset>
      <fieldset class="votePolicy">
        <legend><template v-t="['voting', 'settings_votepolicy']"></template>:</legend>
        <policy-select allow-anonymous="false" :disabled="!isPreparing && !isOffline" :policy="votePolicy" :all-groups="voting.user_groups" @change="setPolicy($event)" ref="policy-select"></policy-select>
      </fieldset>

      <fieldset class="votesMaxVotes">
        <legend><template v-t="['voting', 'settings_maxvotes']"></template>:
          <span class="glyphicon glyphicon-info-sign"
                v-t:aria-label="['voting', 'settings_maxvotes_h']"
                v-tooltip="['voting', 'settings_maxvotes_h']"></span>
        </legend>
        <label class="maxVotesNone">
          <input type="radio" value="0" v-model="maxVotesRestriction" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_maxvotes_none']"></template>
        </label>
        <label class="maxVotesAll">
          <input type="radio" value="1" v-model="maxVotesRestriction" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_maxvotes_limit']"></template>
        </label>
        <label class="maxVotesPerGroup" v-if="votePolicy && votePolicy.id === VOTE_POLICY_USERGROUPS">
          <input type="radio" value="2" v-model="maxVotesRestriction" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_maxvotes_pergroup']"></template>
        </label>
      </fieldset>

      <fieldset class="votesMaxVotesAll inputWithLabelHolder" v-if="maxVotesRestriction == 1">
        <label class="input-group input-group-sm">
          <input type="number" class="form-control" v-model="maxVotesRestrictionAll" :disabled="isOpen || isClosed" autocomplete="off">
          <span class="input-group-addon" v-t="['voting', 'settings_maxvotes_votes']"></span>
        </label>
      </fieldset>

      <fieldset class="votesMaxVotesPerGroup inputWithLabelHolder" v-if="maxVotesRestriction == 2">
        <label class="input-group input-group-sm" v-for="groupId in votePolicy.user_groups">
          <input type="number" class="form-control" :value="getMaxVotesRestrictionPerGroup(groupId)"
                 @change="setMaxVotesRestrictionPerGroup(groupId, $event)"
                 @keyup="setMaxVotesRestrictionPerGroup(groupId, $event)"
                 :disabled="isOpen || isClosed"
                 autocomplete="off">
          <span class="input-group-addon">{{ getGroupName(groupId) }}</span>
        </label>
      </fieldset>

      <fieldset class="majorityTypeSettings" v-if="selectedAnswersHaveMajority">
        <legend><template v-t="['voting', 'settings_majoritytype']"></template>:</legend>
        <label v-for="majorityTypeDef in MAJORITY_TYPES">
          <input type="radio" :value="majorityTypeDef.id" v-model="majorityType" :disabled="isOpen || isClosed">
          {{ majorityTypeDef.name }}
          <span class="glyphicon glyphicon-info-sign"
                :aria-label="majorityTypeDef.description" v-tooltip="majorityTypeDef.description"></span>
        </label>
      </fieldset>
      <fieldset class="generalAbstention" v-if="generalAbstentionIsPossible">
        <label>
          <input type="checkbox" v-model="hasGeneralAbstention">
          <template v-t="['voting', 'settings_generalabstention']"></template>
        </label>
      </fieldset>
      <fieldset class="quorumTypeSettings" v-if="votePolicy.id === VOTE_POLICY_USERGROUPS">
        <legend><template v-t="['voting', 'settings_quorumtype']"></template>:</legend>
        <label v-for="quorumTypeDef in QUORUM_TYPES">
          <input type="radio" :value="quorumTypeDef.id" v-model="quorumType" :disabled="isOpen || isClosed">
          {{ quorumTypeDef.name }}
          <span class="glyphicon glyphicon-info-sign"
                :aria-label="quorumTypeDef.description" v-tooltip="quorumTypeDef.description"></span>
        </label>
      </fieldset>
      <fieldset class="resultsPublicSettings">
        <legend><template v-t="['voting', 'settings_resultspublic']"></template>:</legend>
        <label>
          <input type="radio" value="0" v-model="resultsPublic">
          <template v-t="['voting', 'settings_resultspublic_admins']"></template>
        </label>
        <label>
          <input type="radio" value="1" v-model="resultsPublic">
          <template v-t="['voting', 'settings_resultspublic_all']"></template>
        </label>
      </fieldset>
      <fieldset class="votesPublicSettings">
        <legend><template v-t="['voting', 'settings_votespublic']"></template>:</legend>
        <label>
          <input type="radio" value="0" v-model="votesPublic" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_votespublic_nobody']"></template>
        </label>
        <label>
          <input type="radio" value="1" v-model="votesPublic" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_votespublic_admins']"></template>
        </label>
        <label>
          <input type="radio" value="2" v-model="votesPublic" :disabled="isOpen || isClosed">
          <template v-t="['voting', 'settings_votespublic_all']"></template>
        </label>
        <div class="hint" v-t="['voting', 'settings_votespublic_hint']"></div>
      </fieldset>
      <fieldset class="votesNamesSettings">
        <legend><template v-t="['voting', 'settings_votesnames']"></template>:</legend>
        <label>
          <input type="radio" value="\app\models\settings\VotingBlock::VOTES_NAMES_AUTH ?>" v-model="votesNames">
          <template v-t="['voting', 'settings_votesnames_auth']"></template>
        </label>
        <label>
          <input type="radio" value="\app\models\settings\VotingBlock::VOTES_NAMES_NAME ?>" v-model="votesNames">
          <template v-t="['voting', 'settings_votesnames_name']"></template>
        </label>
        <label>
          <input type="radio" value="\app\models\settings\VotingBlock::VOTES_NAMES_ORGANIZATION ?>" v-model="votesNames">
          <template v-t="['voting', 'settings_votesnames_organization']"></template>
        </label>
      </fieldset>
      <fieldset class="inputWithLabelHolder votesTimer">
        <legend><template v-t="['voting', 'settings_timer']"></template>:
          <span class="glyphicon glyphicon-info-sign"
                v-t:aria-label="['voting', 'settings_timer_h']"
                v-tooltip="['voting', 'settings_timer_h']"></span>
        </legend>
        <label class="input-group input-group-sm">
          <input type="number" class="form-control" v-model="votingTime" autocomplete="off">
          <span class="input-group-addon" v-t="['voting', 'settings_timer_sec']"></span>
        </label>
      </fieldset>
      <label class="assignedMotion">
        <template v-t="['voting', 'settings_motionassign']"></template>:
        <span class="glyphicon glyphicon-info-sign"
              v-t:aria-label="['voting', 'settings_motionassign_h']"
              v-tooltip="['voting', 'settings_motionassign_h']"></span>
        <br>
        <select class="stdDropdown" v-model="settingsAssignedMotion">
          <option :value="''"> - <template v-t="['voting', 'settings_motionassign_none']"></template> - </option>
          <option v-for="motion in motionsAssignable" :value="motion.id">
            {{ motion.title }}
          </option>
        </select>
      </label>
      <button type="submit" class="btn btn-success btnSave" v-t="['voting', 'settings_save']"></button>
      <button type="button" class="btn btn-link btnDelete" @click="deleteVoting()"
              v-t:title="['voting', 'settings_delete']" aria-label="['voting', 'settings_delete']">
        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
      </button>
    </form>
  </section>
</template>

<script>

import translate from "/js/vue/Translate.vue.js";

export default {
  props: ['voting', 'addableMotions', 'alreadyAddedItems', 'userGroups', 'voteDownloadUrl'],
  data() {
    return {
      activityClosed: true,
      addingMotions: false,
      addableMotionSelected: '',
      addingQuestions: false,
      addingQuestionText: '',
      settingsOpened: false,
      changedSettings: {
        // Caching the changed values here prevents unsaved changes to settings from being reset by AJAX polling
        // null = uninitialized
        title: null,
        answerTemplate: null,
        assignedMotion: null,
        majorityType: null,
        maxVotesRestriction: null,
        maxVotesRestrictionAll: null,
        maxVotesRestrictionPerGroup: null,
        quorumType: null,
        votesPublic: null,
        votesNames: null,
        resultsPublic: null,
        hasGeneralAbstention: null,
        votePolicy: null,
        votingTime: null
      },
      shownVoteLists: []
    }
  },
  computed: {
    isUsed: {
      get() {
        return this.voting.status !== this.STATUS_OFFLINE;
      },
      set(val) {
        if (val && this.voting.status === this.STATUS_OFFLINE) {
          this.voting.status = this.STATUS_PREPARING;
          this.statusChanged();
        }
        if (!val) {
          this.voting.status = this.STATUS_OFFLINE;
          this.statusChanged();
        }
      }
    },
    selectedAnswersHaveMajority: function () {
      // Used by the settings form
      return this.answerTemplate === this.ANSWER_TEMPLATE_YES_NO_ABSTENTION || this.answerTemplate === this.ANSWER_TEMPLATE_YES_NO;
    },
    generalAbstentionIsPossible: function () {
      // Used by the settings form
      return this.answerTemplate === this.ANSWER_TEMPLATE_YES;
    },
    settingsTitle: {
      get: function () {
        return (this.changedSettings.title !== null ? this.changedSettings.title : this.voting.title);
      },
      set: function (value) {
        this.changedSettings.title = value;
      }
    },
    majorityType: {
      get: function () {
        return (this.changedSettings.majorityType !== null ? this.changedSettings.majorityType : this.voting.majority_type);
      },
      set: function (value) {
        this.changedSettings.majorityType = value;
      }
    },
    maxVotesRestriction: {
      get: function () {
        if (this.changedSettings.maxVotesRestriction === null) {
          if (this.voting.max_votes_by_group) {
            if (this.voting.max_votes_by_group.length === 1 && this.voting.max_votes_by_group[0].groupId === null) {
              this.changedSettings.maxVotesRestriction = 1;
            } else {
              this.changedSettings.maxVotesRestriction = 2;
            }
          } else {
            this.changedSettings.maxVotesRestriction = 0;
          }
        }
        return this.changedSettings.maxVotesRestriction;
      },
      set: function (value) {
        this.changedSettings.maxVotesRestriction = value;
      }
    },
    maxVotesRestrictionAll: {
      get: function () {
        if (this.changedSettings.maxVotesRestrictionAll === null) {
          if (this.voting.max_votes_by_group && this.voting.max_votes_by_group.length === 1 && this.voting.max_votes_by_group[0].groupId === null) {
            this.changedSettings.maxVotesRestrictionAll = this.voting.max_votes_by_group[0].maxVotes;
          } else {
            this.changedSettings.maxVotesRestrictionAll = '';
          }
        }
        return this.changedSettings.maxVotesRestrictionAll;
      },
      set: function (value) {
        this.changedSettings.maxVotesRestrictionAll = value;
      }
    },
    quorumType: {
      get: function () {
        return (this.changedSettings.quorumType !== null ? this.changedSettings.quorumType : this.voting.quorum_type);
      },
      set: function (value) {
        this.changedSettings.quorumType = value;
      }
    },
    answerTemplate: {
      get: function () {
        return (this.changedSettings.answerTemplate !== null ? this.changedSettings.answerTemplate : this.voting.answers_template);
      },
      set: function (value) {
        this.changedSettings.answerTemplate = value;
      }
    },
    votePolicy: {
      get: function () {
        return (this.changedSettings.votePolicy !== null ? this.changedSettings.votePolicy : this.voting.vote_policy);
      },
      set: function (value) {
        this.changedSettings.votePolicy = value;
      }
    },
    votesPublic: {
      get: function () {
        return (this.changedSettings.votesPublic !== null ? this.changedSettings.votesPublic : this.voting.votes_public);
      },
      set: function (value) {
        this.changedSettings.votesPublic = value;
      }
    },
    votesNames: {
      get: function () {
        return (this.changedSettings.votesNames !== null ? this.changedSettings.votesNames : this.voting.votes_names);
      },
      set: function (value) {
        this.changedSettings.votesNames = value;
      }
    },
    resultsPublic: {
      get: function () {
        return (this.changedSettings.resultsPublic !== null ? this.changedSettings.resultsPublic : this.voting.results_public);
      },
      set: function (value) {
        this.changedSettings.resultsPublic = value;
      }
    },
    hasGeneralAbstention: {
      get: function () {
        return (this.changedSettings.hasGeneralAbstention !== null ? this.changedSettings.hasGeneralAbstention : this.voting.has_general_abstention);
      },
      set: function (value) {
        this.changedSettings.hasGeneralAbstention = value;
      }
    },
    votingTime: {
      get: function () {
        return (this.changedSettings.votingTime !== null ? this.changedSettings.votingTime : this.voting.voting_time);
      },
      set: function (value) {
        this.changedSettings.votingTime = value;
      }
    },
    settingsAssignedMotion: {
      get: function () {
        const origAssigned = (this.voting.assigned_motion !== null ? this.voting.assigned_motion : '');
        return (this.changedSettings.assignedMotion !== null ? this.changedSettings.assignedMotion : origAssigned);
      },
      set: function (value) {
        this.changedSettings.assignedMotion = value;
      }
    },
    motionsAssignable: function () {
      return this.addableMotions.filter(motion => motion.type === 'motion'); // All, save for statute amendments
    },
    resultDownloadLink: function () {
      return this.voteDownloadUrl.replace(/VOTINGBLOCKID/, this.voting.id).replace(/FORMAT/, 'ods');
    },
    quorumIndicator: function () {
      if (this.voting.quorum === null) {
        return this.voting.quorum_custom_target;
      } else {
        const tpl = translate.getTranslation("voting", "quorum_limit");
        return tpl.replace(/%QUORUM%/, this.voting.quorum).replace(/%ALL%/, this.voting.quorum_eligible);
      }
    },
    abstentionsStr: function () {
      if (this.voting.abstentions_total === 1) {
        return translate.getTranslation("voting", "voting_abstentions_1");
      } else {
        const tpl = translate.getTranslation("voting", "voting_abstentions_x");
        return tpl.replace(/%NUM%/, this.voting.abstentions_total);
      }
    }
  },
  methods: {
    formatLogEntry: function (logEntry) {
      let description = '?';
      switch (logEntry['type']) {
        case this.ACTIVITY_TYPE_OPENED:
          description = translate.getTranslation("voting", "activity_opened");
          break;
        case this.ACTIVITY_TYPE_RESET:
          description = translate.getTranslation("voting", "activity_reset");
          break;
        case this.ACTIVITY_TYPE_CLOSED:
          description = translate.getTranslation("voting", "activity_closed");
          break;
        case this.ACTIVITY_TYPE_REOPENED:
          description = translate.getTranslation("voting", "activity_reopened");
          break;
      }
      let date = new Date(logEntry['date']);
      return date.toLocaleString() + ': ' + description;
    },
    getVotingMajority: function (voting) {
      if (!this.votingHasMajority) {
        return null;
      }

      return Object.values(this.MAJORITY_TYPES).find(majorityType => {
        return majorityType.id === voting.majority_type;
      });
    },
    getVotingQuorum: function (voting) {
      if (!this.votingHasQuorum) {
        return null;
      }
      return Object.values(this.QUORUM_TYPES).find(quorumType => {
        return quorumType.id === voting.quorum_type;
      });
    },
    getGroupName: function (groupId) {
      return this.userGroups.find(group => group.id == groupId).title;
    },
    removeItem: function (groupedVoting) {
      this.$emit('remove-item', this.voting.id, groupedVoting[0].type, groupedVoting[0].id);
    },
    addIMotion: function ($event) {
      if ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }
      this.$emit('add-imotion', this.voting.id, this.addableMotionSelected);
      this.addableMotionSelected = '';
    },
    openQuestionAdder: function() {
      this.addingQuestions = true;
      window.setTimeout(() => {
        document.getElementById('voting_question_' + this.voting.id).focus();
      }, 1);
    },
    addQuestion: function ($event) {
      if ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }
      this.$emit('add-question', this.voting.id, this.addingQuestionText);
      this.addingQuestionText = '';
      this.addingQuestions = false;
    },
    isAddable: function (item) {
      if (item.type === 'motion') {
        return this.alreadyAddedItems.motions.indexOf(item.id) === -1;
      }
      if (item.type === 'amendment') {
        return this.alreadyAddedItems.amendments.indexOf(item.id) === -1;
      }
      return false;
    },
    openVoting: function ($event) {
      if ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }
      this.voting.status = this.STATUS_OPEN;
      this.statusChanged();
    },
    closeVoting: function (publish, $event) {
      $event.preventDefault();
      $event.stopPropagation();

      if (publish) {
        this.voting.status = this.STATUS_CLOSED_PUBLISHED;
      } else {
        this.voting.status = this.STATUS_CLOSED_UNPUBLISHED;
      }

      this.statusChanged();
    },
    resetVoting: function () {
      const widget = this,
          newStatus = this.STATUS_PREPARING;
      bootbox.confirm(translate.getTranslation("voting", "admin_reset_dialog"), function(result) {
        if (result) {
          widget.voting.status = newStatus;
          widget.statusChanged();
        }
      });
    },
    reopenVoting: function () {
      this.voting.status = this.STATUS_OPEN;
      this.statusChanged();
    },
    publishVoting: function () {
      this.voting.status = this.STATUS_CLOSED_PUBLISHED;
      this.statusChanged();
    },
    statusChanged: function () {
      this.$emit('set-status', this.voting.id, this.voting.status);
    },
    openActivities: function () {
      this.activityClosed = false;
    },
    closeActivities: function () {
      this.activityClosed = true;
    },
    itemAdminUrl: function (item) {
      if (item.type === 'motion') {
        return this.motionEditUrl.replace(/00000000/, item.id);
      }
      if (item.type === 'amendment') {
        return this.amendmentEditUrl.replace(/00000000/, item.id);
      }
      return null;
    },
    hasVoteList: function (groupedItem) {
      return groupedItem[0].votes !== undefined && (this.isOpen || this.isClosed);
    },
    isVoteListShown: function (groupedItem) {
      const showId = groupedItem[0].type + '-' + groupedItem[0].id;
      return this.shownVoteLists.indexOf(showId) !== -1;
    },
    showVoteList: function (groupedItem) {
      const showId = groupedItem[0].type + '-' + groupedItem[0].id;
      this.shownVoteLists.push(showId);
    },
    hideVoteList: function (groupedItem) {
      const hideId = groupedItem[0].type + '-' + groupedItem[0].id;
      this.shownVoteLists = this.shownVoteLists.filter(id => id !== hideId);
    },
    setVotersToUserGroup: function (userIds, newUserGroup) {
      this.$emit('set-voters-to-user-group', this.voting.id, userIds, newUserGroup);
    },
    setMaxVotesRestrictionAll: function (val) { // Called from the tests
      this.maxVotesRestrictionAll = val;
    },
    initMaxVotesRestrictionPerGroup: function () {
      if (this.changedSettings.maxVotesRestrictionPerGroup !== null) {
        return;
      }

      const setValues = {};
      if (this.voting.max_votes_by_group) {
        this.voting.max_votes_by_group.forEach(group => {
          if (group.groupId) {
            setValues[group.groupId.toString()] = group.maxVotes;
          }
        });
      }

      this.changedSettings.maxVotesRestrictionPerGroup = []
      this.votePolicy.user_groups.forEach(groupId => {
        const setValue = setValues[groupId.toString()];
        this.changedSettings.maxVotesRestrictionPerGroup[groupId] = (setValue !== undefined ? setValue : '');
      });
    },
    getMaxVotesRestrictionPerGroup: function(groupId) {
      this.initMaxVotesRestrictionPerGroup();
      return this.changedSettings.maxVotesRestrictionPerGroup[groupId];
    },
    setMaxVotesRestrictionPerGroup: function(groupId, $event) {
      $event.stopPropagation();
      this.changedSettings.maxVotesRestrictionPerGroup[groupId] = $event.target.value;
    },
    openSettings: function () {
      this.settingsOpened = true;
    },
    closeSettings: function () {
      this.settingsOpened = false;
    },
    saveSettings: function ($event) {
      if ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }

      let maxVotesSettings = null;
      if (this.maxVotesRestriction == 1) {
        maxVotesSettings = [{
          "groupId": null,
          "maxVotes": this.maxVotesRestrictionAll
        }];
      }
      if (this.maxVotesRestriction == 2) {
        maxVotesSettings = [];
        Object.keys(this.changedSettings.maxVotesRestrictionPerGroup).forEach(groupId => {
          maxVotesSettings.push({
            "groupId": parseInt(groupId, 10),
            "maxVotes": parseInt(this.changedSettings.maxVotesRestrictionPerGroup[groupId], 10)
          })
        });
      }

      this.$emit('save-settings', this.voting.id, this.settingsTitle, this.answerTemplate, this.majorityType, this.quorumType, this.hasGeneralAbstention, this.votePolicy, maxVotesSettings, this.resultsPublic, this.votesPublic, this.votingTime, this.settingsAssignedMotion, this.changedSettings.votesNames);
      this.changedSettings.votesPublic = null;
      this.changedSettings.votesNames = null;
      this.changedSettings.majorityType = null;
      this.changedSettings.quorumType = null;
      this.changedSettings.hasGeneralAbstention = null;
      this.changedSettings.answerTemplate = null;
      this.changedSettings.votePolicy = null;
      this.changedSettings.votingTime = null;
      this.changedSettings.maxVotesRestriction = null;
      this.changedSettings.maxVotesRestrictionAll = null;
      this.changedSettings.maxVotesRestrictionPerGroup = null;
      this.settingsOpened = false;
    },
    setPolicy: function (data) {
      this.votePolicy = data;
      if (this.votePolicy.id != this.POLICY_USER_GROUPS && this.maxVotesRestriction == 2) {
        this.maxVotesRestriction = 0;
      }
    },
    deleteVoting: function () {
      const widget = this;
      const deleteConfirmation = translate.getTranslation("voting", "settings_delete_bb");
      bootbox.confirm(deleteConfirmation, function(result) {
        if (result) {
          widget.$emit('delete-voting', widget.voting.id);
        }
      });
    }
  },
  updated() {
    $(this.$el).find('[data-toggle="tooltip"]').tooltip();
  },
  beforeMount() {
    this.startPolling();
  },
  beforeUnmount() {
    this.stopPolling();
  }
};
</script>
