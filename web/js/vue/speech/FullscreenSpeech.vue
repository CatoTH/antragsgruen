<template>
  <article class="speechLists currentSpeechPageWidth">
    <div class="content" v-if="queue">
      <div class="activeSpeaker">
        <span class="glyphicon glyphicon-comment leftIcon" aria-hidden="true"></span>
        <span v-if="activeSpeaker" class="existing" v-t="['speech', 'current', false, {}, ':']"></span>
        <div v-if="activeSpeaker" class="name">{{ activeSpeaker.name }}</div>
        <span v-if="!activeSpeaker" class="notExisting" v-t="['speech', 'current_nobody']"></span>
      </div>
      <div class="remainingTime" v-if="activeSpeaker && hasSpeakingTime && remainingSpeakingTime !== null">
        <template v-t="['speech', 'remaining_time', false, {}, ':']"></template>
        <span v-if="remainingSpeakingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
        <span v-if="remainingSpeakingTime < 0" class="over" v-t="['speech', 'remaining_time_over']"></span>
      </div>
      <div v-if="upcomingSpeakers.length > 0" class="upcomingSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        <template v-t="['speech', 'next_speaker', false, {}, ':']"></template>
        <ul class="upcomingSpeakerList">
          <li v-for="speaker in upcomingSpeakers">
            <span class="name">{{ speaker.name }}</span><!-- Fight unwanted whitespace
                --><span class="label label-success" v-if="isMe(speaker)" v-t="['speech', 'you']"></span><!-- Fight unwanted whitespace
                --><button type="button" class="btn btn-link btnWithdraw" v-if="isMe(speaker)"
                           @click="removeMeFromQueue($event)"
                           v-t:title="['speech', 'apply_revoke_aria']" v-t:aria-label="['speech', 'apply_revoke_aria']">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            <span class="withdrawLabel" v-t="['speech', 'apply_revoke']"></span>
          </button>
          </li>
        </ul>
      </div>
    </div>

    <div v-if="queue">
      <section class="waiting waitingSingle" v-if="queue.subqueues.length === 1" v-t:aria-label="['speech', 'waiting_aria_1']">
        <h2 class="green"><template  v-t="['speech', 'waiting_list', false, {}, ':']"></template> {{ queue.subqueues[0].num_applied }}</h2>

        <ol class="nameList" v-if="queue.subqueues[0].applied && queue.subqueues[0].applied.length > 0" v-t:title="['speech', 'persons_waiting']">
          <li v-for="applied in queue.subqueues[0].applied">
            <span class="glyphicon glyphicon-time leftIcon" aria-hidden="true"></span>
            {{ applied.name }}
          </li>
        </ol>
      </section>

      <section class="waiting waitingMultiple" v-if="queue.subqueues.length > 1" v-t:aria-label="['speech', 'waiting_aria_x']">
        <h2 class="green" v-t="['speech', 'waiting_list_x']"></h2>

        <div class="waitingSubqueues">
          <div v-for="subqueue in queue.subqueues" class="subqueue">
            <div class="header">
                    <span class="name">
                        {{ subqueue.name }}
                    </span>

              <span class="number" v-t:title="['speech', 'persons_waiting']">
                        <span class="glyphicon glyphicon-time" v-t:aria-label="['speech', 'persons_waiting']"></span>
                        {{ subqueue.num_applied }}
                    </span>
            </div>
            <div class="applied">
              <ol class="nameList" v-if="subqueue.applied && subqueue.applied.length > 0 && showApplicationForm !== subqueue.id && showApplicationForm !== subqueue.id + '_poo'" v-t:title="['speech', 'persons_waiting']">
                <li v-for="applied in subqueue.applied" v-html="formatUsernameHtml(applied)"></li>
              </ol>
            </div>
          </div>
        </div>
      </section>
    </div>
  </article>
</template>

<script>
import { getSpeechCommonMixins } from "/js/vue/speech/SpeechCommonMixins.js";
const MIXINS = getSpeechCommonMixins();

export default {
  props: ['initQueue', 'csrf', 'user', 'title'],
  mixins: [MIXINS],
  beforeMount() {
    this.setHighFrequency(true);
    this.startPolling();
  },
  beforeUnmount() {
    this.stopPolling();
  }
}
</script>
