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
        props: ['subqueue'],
        data() {
            console.log(JSON.parse(JSON.stringify(this.subqueue)));
            return {
                adderOpened: false,
                adderName: ''
            };
        },
        computed: {},
        methods: {
            onItemSelected: function ($event, item) {
                $event.preventDefault();
                this.$emit('add-item-to-slots', item);
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
            }
        }
    });
</script>
