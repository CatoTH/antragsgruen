<?php
ob_start();
?>

<section class="subqueue" aria-label="Warteliste {{ subqueue.name }}">
    <header v-if="subqueue.name !== 'default'">{{ subqueue.name }}</header>
    <header v-if="subqueue.name === 'default'">Warteliste</header>

    <ul class="subqueueItems">
        <li v-for="item in subqueue.applied" class="subqueueItem" tabindex="0"
            v-on:click="onItemSelected($event, item)"
            v-on:keyup.enter="onItemSelected($event, item)"
            title="Auf die Redeliste setzen" aria-label="Auf die Redeliste setzen"
        >
            {{ item.name }}

            <div class="operations" v-if="otherSubqueues.length > 0">
                <button class="link moveSubqueue" type="button" v-if="otherSubqueues.length === 1" ref="otherQueue"
                        title="In die andere Warteliste verschieben"
                        v-on:click="moveToSubqueue($event, item, otherSubqueues[0])"
                >
                    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                    <span class="sr-only">In die andere Warteliste verschieben</span>
                </button>

                <div class="btn-group" v-if="otherSubqueues.length > 1" ref="otherQueues">
                    <button class="link moveSubqueue dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            title="In eine andere Warteliste verschieben"
                    >
                        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                        <span class="sr-only">In eine andere Warteliste verschieben</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li v-for="newSubqueue of otherSubqueues">
                            <a href="#" v-on:click="moveToSubqueue($event, item, newSubqueue)">{{ newSubqueue.name }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </li>
    </ul>

    <div class="empty" v-if="subqueue.applied.length === 0">
        keine Bewerbungen
    </div>

    <section class="subqueueAdder">
        <button class="link" type="button" v-if="!adderOpened" v-on:click="openAdder()" v-on:keyup.enter="openAdder()">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            Hinzufügen
        </button>
        <form method="POST" v-on:submit="onAdderSubmitted($event)" v-if="adderOpened">
            <label v-bind:for="'subqueueAdderName' + subqueue.id" class="sr-only">Name</label>
            <div class="input-group">
                <input type="text" class="form-control" ref="adderNameInput" v-model="adderName" v-bind:id="'subqueueAdderName' + subqueue.id"
                       required placeholder="Name">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit">Eintragen</button>
                </span>
            </div>
        </form>
    </section>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('speech-admin-subqueue', {
        template: <?= json_encode($html) ?>,
        props: ['subqueue', 'allSubqueues'],
        data() {
            return {
                adderOpened: false,
                adderName: ''
            };
        },
        computed: {
            otherSubqueues: function () {
                console.log("allSubqueues:", this.subqueue.id);
                const mySubqueueId = this.subqueue.id;
                return this.allSubqueues.filter(function (subqueue) {
                    return subqueue.id && subqueue.id !== mySubqueueId
                });
            }
        },
        methods: {
            _isButtonClick: function ($event) {
                let isButton = false;
                if (this.$refs.otherQueues) {
                    this.$refs.otherQueues.forEach(function (button) {
                        if ($event.target === button || button.contains($event.target)) {
                            isButton = true;
                        }
                    });
                }
                if (this.$refs.otherQueue) {
                    this.$refs.otherQueue.forEach(function (button) {
                        if ($event.target === button || button.contains($event.target)) {
                            isButton = true;
                        }
                    });
                }
                return isButton;
            },
            onItemSelected: function ($event, item) {
                if (this._isButtonClick($event)) {
                    return;
                }
                $event.preventDefault();
                // this.$emit('add-item-to-slots', item);
                this.$emit('add-item-to-slots-and-start', item);
            },
            openAdder: function () {
                this.adderOpened = true;
                this.$nextTick(function () {
                    this.$refs.adderNameInput.focus();
                });
            },
            onAdderSubmitted: function ($event) {
                $event.preventDefault();
                if (this.adderName) {
                    this.$emit('add-item-to-subqueue', this.subqueue, this.adderName);
                    this.adderOpened = false;
                    this.adderName = '';
                }
            },
            moveToSubqueue: function ($event, item, newSubqueue) {
                $event.preventDefault();
                this.$emit('move-item-to-subqueue', item, newSubqueue);
            }
        }
    });
</script>
