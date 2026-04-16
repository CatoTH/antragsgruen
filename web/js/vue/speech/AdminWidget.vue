<template>
  <article class="speechAdmin" :class="{dragging: dragging}">
    <div class="toolbarBelowTitle settings">
      <div class="settingsActive" v-if="queue.is_active">
        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
        <template v-t="['speech', 'admin_is_active']"></template>

        <button class="btn btn-xs btn-default" type="button" @click="setInactive()" v-t="['speech', 'admin_deactivate']"></button>
      </div>
      <div class="settingsActive" v-if="!queue.is_active">
        <span class="inactive" v-t="['speech', 'admin_is_inactive']"></span>
        <button class="btn btn-xs btn-default" type="button" @click="setActive()" v-t="['speech', 'admin_activate']"></button>
        <span v-if="queue.other_active_name" class="deactivateOthers" v-t="['speech', 'admin_deactivate_other']"></span>
      </div>
      <div class="settingsOpen">
        <label class="settingOpen" v-if="queue.is_active">
          <input type="checkbox" v-model="queue.settings.is_open" @change="settingsChanged()">
          <template v-t="['speech', 'admin_setting_open']"></template>
        </label>
        <label class="settingOpenPoo" v-if="queue.is_active">
          <input type="checkbox" v-model="queue.settings.is_open_poo" @change="settingsChanged()">
          <template v-t="['speech', 'admin_setting_open_poo']"></template>
        </label>
      </div>
      <div class="settingsPolicy">
        <div class="btn-group">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <template v-t="['speech', 'admin_setting']"></template>
            <span class="caret" aria-hidden="true"></span>
          </button>
          <ul class="dropdown-menu">
            <li class="checkbox">
              <label @click="$event.stopPropagation()">
                <input type="checkbox" class="allowCustomNames" v-model="queue.settings.allow_custom_names" @change="settingsChanged()">
                <template v-t="['speech', 'admin_allow_custom_names']"></template>
              </label>
            </li>
            <li class="checkbox">
              <label @click="$event.stopPropagation()">
                <input type="checkbox" class="preferNonspeaker" v-model="queue.settings.prefer_nonspeaker" @change="settingsChanged()">
                <template v-t="['speech', 'admin_prefer_nonspeak']"></template>
              </label>
            </li>
            <li class="checkbox">
              <label @click="$event.stopPropagation()">
                <input type="checkbox" class="showNames" v-model="queue.settings.show_names" @change="settingsChanged()">
                <template v-t="['speech', 'admin_show_names']"></template>
              </label>
            </li>
            <li class="checkbox">
              <label @click="$event.stopPropagation()">
                <input type="checkbox" class="hasSpeakingTime" v-model="hasSpeakingTime" @change="settingsChanged()">
                <template v-t="['speech', 'admin_speaking_time']"></template>
              </label>
            </li>
            <li v-if="hasSpeakingTime" class="speakingTime">
              <label @click="$event.stopPropagation()" class="input-group input-group-sm">
                <input type="number" class="form-control" v-model="speakingTime" @change="settingsChanged()">
                <span class="input-group-addon" v-t="['speech', 'admin_time_seconds']"></span>
              </label>
            </li>
            <li class="link">
              <a :href="componentAdminLink">
                <span class="icon glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <template v-t="['speech', 'admin_goto_components']"></template>
              </a>
            </li>
            <li class="randomizeQueues">
              <button class="btn btn-default btn-sm" type="button" @click="randomizeQueues($event)" v-t="['speech', 'admin_randomize_queues']"></button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <main class="content">
      <section class="previousSpeakers" :class="{previousShown: showPreviousList, invisible: !hasPreviousSpeakers}">
        <header>
          <template v-t="['speech', 'admin_prev_speakers', false, {}, ':']"></template>
          {{ previousSpeakers.length }}

          <button class="btn btn-link" type="button" @click="showPreviousList = true" v-if="!showPreviousList">
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
            <template v-t="['speech', 'admin_prev_show']"></template>
          </button>
          <button class="btn btn-link" type="button" @click="showPreviousList = false" v-if="showPreviousList">
            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
            <template v-t="['speech', 'admin_prev_show']"></template>
          </button>
        </header>

        <div class="previousLists" v-if="showPreviousList">
          <div class="previousList" v-for="subqueue in queue.subqueues">
            <header v-if="queue.subqueues.length > 1 && subqueue.name !== 'default'"><span>{{ subqueue.name }}</span></header>
            <header v-if="queue.subqueues.length > 1 && subqueue.name === 'default'"><span v-t="['speech', 'waiting_list_1']"></span></header>
            <ol>
              <li v-for="item in getPreviousForSubqueue(subqueue)" v-html="formatUsernameHtml(item)"></li>
            </ol>
          </div>
        </div>
      </section>

      <ol class="slots" v-t:aria-label="['speech', 'speaking_list']">
        <li v-if="activeSlot" class="slotEntry slotActive active">
          <span class="glyphicon glyphicon-comment iconBackground" aria-hidden="true"></span>

          <div class="status statusActive" v-t="['speech', 'admin_running']"></div>

          <div class="name" v-html="formatUsernameHtml(activeSlot)"></div>

          <div class="remainingTime" v-if="hasSpeakingTime && remainingSpeakingTime !== null">
            <template v-t="['speech', 'remaining_time', false, {}, ':']"></template>
            <span v-if="remainingSpeakingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
            <span v-if="remainingSpeakingTime < 0" class="over" v-t="['speech', 'remaining_time_over']"></span>
          </div>

          <button type="button" class="btn btn-danger stop" @click="stopSlot($event, activeSlot)">
            <span class="glyphicon glyphicon-stop" v-t:title="['speech', 'admin_stop']" aria-hidden="true"></span>
            <span class="sr-only" v-t="['speech', 'admin_stop']"></span>
          </button>

          <div class="operations">
            <button type="button" class="link removeSlot" @click="removeSlot($event, activeSlot)" v-t:title="['speech', 'admin_back_to_wait']">
              <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
              <span class="sr-only" v-t="['speech', 'admin_back_to_wait']"></span>
            </button>
          </div>
        </li>
        <li v-if="!activeSlot" class="slotEntry slotActive inactive">
          <span class="glyphicon glyphicon-comment iconBackground" aria-hidden="true"></span>

          <div class="status statusActive" v-t="['speech', 'admin_running', false, {}, ':']"></div>

          <div class="nameNobody" v-t="['speech', 'admin_running_nobody']"></div>
        </li>

        <li v-for="slot in sortedQueue" class="slotEntry">
          <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>

          <div class="name" v-html="formatUsernameHtml(slot)"></div>

          <button type="button" class="btn btn-success start" @click="startSlot($event, slot)" v-t:title="['speech', 'admin_start']">
            <span class="glyphicon glyphicon-play" aria-hidden="true"></span>
            <span class="sr-only" v-t="['speech', 'admin_start']"></span>
          </button>

          <div class="operations">
            <button type="button" class="link removeSlot" @click="removeSlot($event, slot)" v-t:title="['speech', 'admin_back_to_wait']">
              <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
              <span class="sr-only" v-t="['speech', 'admin_back_to_wait']"></span>
            </button>
          </div>
        </li>

        <li class="slotPlaceholder active" v-if="slotProposal"
            @click="addItemToSlotsAndStart(slotProposal.id)"
            @keyup.enter="addItemToSlotsAndStart(slotProposal.id)">
          <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>
          <div class="title" v-t="['speech', 'admin_next', false, {}, ':']"></div>
          <div class="name" v-html="formatUsernameHtml(slotProposal)"></div>

          <div class="operationDelete" @click="onItemDelete($event, slotProposal.id)" @keyup.enter="onItemDelete($event, item)" tabindex="0">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            <span v-t="['speech', 'admin_delete']"></span>
          </div>

          <div class="operationStart" @click="addItemToSlotsAndStart(slotProposal.id)" @keyup.enter="addItemToSlotsAndStart(slotProposal.id)" tabindex="0">
            <span class="glyphicon glyphicon-play" aria-hidden="true"></span>
            <span v-t="['speech', 'admin_start']"></span>
          </div>
        </li>
        <li class="slotPlaceholder inactive" v-if="!slotProposal">
          <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>
          <div class="title" v-t="['speech', 'admin_start_proposal', false, {}, ':']"></div>
          <div class="nameNobody" v-t="['speech', 'admin_proposal_nobody']"></div>
        </li>
      </ol>

      <div class="subqueues">
        <speech-admin-subqueue v-for="(subqueue, index) in queue.subqueues"
                               :subqueue="subqueue"
                               :allSubqueues="queue.subqueues"
                               :position="index > 0 ? 'right' : 'left'"
                               @add-item-to-slots-and-start="addItemToSlotsAndStart"
                               @add-item-to-subqueue="addItemToSubqueue"
                               @delete-item="deleteItem"
                               @move-item-to-subqueue="moveItemToSubqueue"
                               @item-drag-start="itemDragStart"
                               @item-drag-end="itemDragEnd"
                               ref="subqueueWidgets"
        ></speech-admin-subqueue>
      </div>
    </main>

    <section class="queueResetSection">
      <button type="button" class="btn btn-link btn-danger" @click="resetQueue()">
        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        <template v-t="['speech', 'admin_reset']"></template>
      </button>
    </section>
  </article>
</template>

<script>
export default {
  props: ['initQueue', 'csrf', 'componentAdminLink', 'pollUrl', 'itemPerformOperationUrl', 'randomizeQueueUrl', 'resetQueueUrl', 'createItemUrl', 'setStatusUrl'],
  data() {
    return {
      queue: null,
      showPreviousList: false,
      pollingId: null,
      liveConnected: false,
      timerId: null,
      dragging: false,
      changedSettings: {
        hasSpeakingTime: null,
        speakingTime: null
      },
      remainingSpeakingTime: null
    };
  },
  watch: {
    initQueue: {
      handler(newVal) {
        this.queue = newVal;
      },
      immediate: true
    }
  },
  computed: {
    hasSpeakingTime: {
      get: function () {
        return (this.changedSettings.hasSpeakingTime !== null ? this.changedSettings.hasSpeakingTime : this.queue.settings.speaking_time !== null);
      },
      set: function (value) {
        this.changedSettings.hasSpeakingTime = value;
      }
    },
    speakingTime: {
      get: function () {
        return (this.changedSettings.speakingTime !== null ? this.changedSettings.speakingTime : this.queue.settings.speaking_time);
      },
      set: function (value) {
        this.changedSettings.speakingTime = value;
      }
    },
    previousSpeakers: function () {
      return this.queue.slots.filter(function (slot) {
        return slot.date_stopped !== null;
      }).sort(function (slot1, slot2) {
        const date1 = new Date(slot1.date_stopped);
        const date2 = new Date(slot2.date_stopped);
        return date1.getTime() - date2.getTime();
      });
    },
    hasPreviousSpeakers: function () {
      return this.queue.slots.filter(function (slot) {
        return slot.date_stopped !== null;
      }).length > 0;
    },
    formattedRemainingTime: function () {
      const minutes = Math.floor(this.remainingSpeakingTime / 60);
      let seconds = this.remainingSpeakingTime - minutes * 60;
      if (seconds < 10) {
        seconds = "0" + seconds;
      }

      return minutes + ":" + seconds;
    },
    sortedQueue: function () {
      return this.queue.slots.filter(function (slot) {
        return slot.date_started === null;
      }).sort(function (slot1, slot2) {
        if (slot1.is_point_of_order && !slot2.is_point_of_order) {
          return -1;
        }
        if (slot2.is_point_of_order && !slot1.is_point_of_order) {
          return 1;
        }
        return slot1.position - slot2.position;
      });
    },
    activeSlot: function () {
      const active = this.queue.slots.filter(function (slot) {
        return slot.date_started !== null && slot.date_stopped === null;
      });
      return active.length > 0 ? active[0] : null;
    },
    slotProposal: function () {
      const prevSpeakerNums = {};
      this.queue.slots.forEach(function (slot) {
        if (prevSpeakerNums[slot.subqueue.id] === undefined) {
          prevSpeakerNums[slot.subqueue.id] = 0;
        }
        prevSpeakerNums[slot.subqueue.id]++;
      });

      const queuesSortedByPreviousSpeakers = Object.assign([], this.queue.subqueues).sort(function (queue1, queue2) {
        const num1 = (prevSpeakerNums[queue1.id] === undefined ? 0 : prevSpeakerNums[queue1.id]);
        const num2 = (prevSpeakerNums[queue2.id] === undefined ? 0 : prevSpeakerNums[queue2.id]);
        if (num1 === num2) {
          return queue1.position - queue2.position; // First positions come first, if same amount of speakers
        } else {
          return num1 - num2; // Lower numbers first
        }
      });

      // If queue no. 1 is empty and had less previous talkers than queue no. 2, we're not making a proposal

      const chosenQueue = queuesSortedByPreviousSpeakers[0];
      if (chosenQueue.applied.length === null) {
        return null;
      }

      if (this.queue.settings.prefer_nonspeaker) {
        // @TODO respect user ID + name
        return chosenQueue.applied[0];
      } else {
        return chosenQueue.applied[0];
      }
    }
  },
  methods: {
    _performOperation: function (itemId, op, additionalProps) {
      let postData = {
        _csrf: this.csrf,
      };
      if (additionalProps) {
        postData = Object.assign(postData, additionalProps);
      }
      const widget = this;
      const url = this.itemPerformOperationUrl
          .replace(/QUEUEID/, widget.queue.id)
          .replace(/ITEMID/, itemId)
          .replace(/OPERATION/, op);
      $.post(url, postData, function (data) {
        widget.queue = data;
      }).catch(function (err) {
        alert(err.responseText);
      });
    },
    getPreviousForSubqueue: function (subqueue) {
      return this.previousSpeakers.filter(function (item) {
        return item.subqueue.id === subqueue.id ||
            (item.subqueue.id === null && subqueue.id === -1);
      });
    },
    startSlot: function ($event, slot) {
      $event.preventDefault();
      this._performOperation(slot.id, "start");
    },
    stopSlot: function ($event, slot) {
      $event.preventDefault();
      this._performOperation(slot.id, "stop");
    },
    removeSlot: function ($event, slot) {
      $event.preventDefault();
      this._performOperation(slot.id, "unset-slot");
    },
    // Not used currently
    addItemToSlots: function (item) {
      this._performOperation(item.id, "set-slot");
    },
    addItemToSlotsAndStart: function (itemId) {
      this._performOperation(itemId, "set-slot-and-start");
    },
    onItemDelete: function ($event, itemId) {
      $event.preventDefault();
      $event.stopPropagation();
      this._performOperation(itemId, "delete");
    },
    deleteItem: function (itemId) {
      this._performOperation(itemId, "delete");
    },
    moveItemToSubqueue: function (itemId, newSubqueueId, position) {
      this._performOperation(itemId, "move", {newSubqueueId, position});
      this.itemDragEnd();
    },
    itemDragStart: function (itemId) {
      this.dragging = true;
      this.$refs.subqueueWidgets.forEach(subqueue => {
        subqueue.onWidgetDragStart.bind(subqueue)(itemId);
      });
    },
    itemDragEnd: function () {
      this.dragging = false;
      this.$refs.subqueueWidgets.forEach(subqueue => {
        subqueue.onWidgetDragEnd.bind(subqueue)();
      });
    },
    setInactive: function () {
      this.queue.is_active = false;
      this.settingsChanged();
    },
    setActive: function () {
      this.queue.is_active = true;
      this.settingsChanged();
    },
    resetQueue: function () {
      const widget = this;
      bootbox.confirm(resetConfirmation, function(result) {
        if (result) {
          $.post(this.resetQueueUrl.replace(/QUEUEID/, widget.queue.id), { _csrf: widget.csrf }, function (data) {
            widget.queue = data;
          }).catch(function (err) {
            alert(err.responseText);
          });
        }
      });
    },
    settingsChanged: function () {
      const widget = this;
      $.post(this.setStatusUrl.replace(/QUEUEID/, widget.queue.id), {
        is_active: (this.queue.is_active ? 1 : 0),
        is_open: (this.queue.settings.is_open ? 1 : 0),
        is_open_poo: (this.queue.settings.is_open_poo ? 1 : 0),
        prefer_nonspeaker: (this.queue.settings.prefer_nonspeaker ? 1 : 0),
        allow_custom_names: (this.queue.settings.allow_custom_names ? 1 : 0),
        show_names: (this.queue.settings.show_names ? 1 : 0),
        speaking_time: (this.hasSpeakingTime ? (this.speakingTime > 0 ? parseInt(this.speakingTime, 10) : 60) : null),
        _csrf: this.csrf,
      }, function (data) {
        widget.queue = data['queue'];

        widget.changedSettings.speakingTime = null;
        widget.changedSettings.hasSpeakingTime = null;

        if (data['sidebar'] && data['sidebar'][0] !== '') {
          document.getElementById('sidebar').childNodes.item(0).innerHTML = data['sidebar'][0];
          // @TODO Secondary sidebar
        }
      }).catch(function (err) {
        alert(err.responseText);
      });
    },
    randomizeQueues: function ($event) {
      $event.preventDefault();
      $event.stopPropagation();
      const widget = this;
      $.post(this.randomizeQueueUrl.replace(/QUEUEID/, widget.queue.id), { _csrf: widget.csrf }, function (data) {
        widget.queue = data;
      }).catch(function (err) {
        alert(err.responseText);
      });
    },
    addItemToSubqueue: function (subqueue, itemName) {
      const widget = this;
      $.post(this.createItemUrl.replace(/QUEUEID/, widget.queue.id), {
        subqueue: subqueue.id,
        name: itemName,
        _csrf: this.csrf,
      }, function (data) {
        widget.queue = data;
      }).catch(function (err) {
        alert(err.responseText);
      });
    },
    recalcTimeOffset: function (serverTime) {
      const browserTime = (new Date()).getTime();
      this.timeOffset = browserTime - serverTime.getTime();
    },
    recalcRemainingTime: function () {
      const active = this.activeSlot;
      if (!active) {
        this.remainingSpeakingTime = null;
        return;
      }
      const startedTs = (new Date(active.date_started)).getTime();
      const currentTs = (new Date()).getTime() - this.timeOffset;
      const secondsPassed = Math.round((currentTs - startedTs) / 1000);

      this.remainingSpeakingTime = this.queue.settings.speaking_time - secondsPassed;
    },
    setData: function (data) {
      this.queue = data;
      this.recalcTimeOffset(new Date(data['current_time']));
      this.recalcRemainingTime();
    },
    reloadData: function () {
      const widget = this;
      if (widget.liveConnected) {
        return;
      }

      $.get(
          this.pollUrl.replace(/QUEUEID/, widget.queue.id),
          this.setData.bind(this)
      ).catch(function(err) {
        console.error("Could not load speech queue data from backend", err);
      });
    },
    formatUsernameHtml: function (item) {
      let name = item.name;
      name = name.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");

      // Replaces patterns like [[Remote]] by labels.
      return name.replaceAll(/\[\[(.*)]]/g, "<span class=\"label label-info\">$1</span>");
    },
    startPolling: function () {
      this.recalcTimeOffset(new Date());

      const widget = this;
      this.pollingId = window.setInterval(function () {
        widget.reloadData();
      }, 1000);

      this.timerId = window.setInterval(function () {
        widget.recalcRemainingTime();
      }, 100);

      if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
        window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('admin', 'speech', (connectionEvent, speechEvent) => {
          if (connectionEvent !== null) {
            widget.liveConnected = connectionEvent;
          }
          if (speechEvent !== null) {
            this.setData(speechEvent);
          }
        });
      }
    }
  },
  beforeUnmount() {
    window.clearInterval(this.pollingId);
    window.clearInterval(this.timerId);
  },
  created() {
    this.startPolling();
  }
}
</script>

