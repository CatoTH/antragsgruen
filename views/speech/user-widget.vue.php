<?php

ob_start();

use app\components\UrlHelper; ?>

<div>

    Wartelisten:
    <div v-for="subqueue in queue.subqueues" class="row">
        <div class="col-md-3">
            {{ subqueue.name }}
        </div>
        <div class="col-md-1">
            {{ subqueue.numApplied }}
        </div>
        <form v-on:submit="register($event, subqueue)" v-if="!queue.iAmOnList" class="col-md-5">
            <label v-bind:for="'speechRegisterName' + subqueue.id" class="sr-only">Name</label>
            <div class="input-group">
                <input type="text" class="form-control" v-model="registerName" v-bind:id="'speechRegisterName' + subqueue.id">
                <span class="input-group-btn">
                        <button class="btn btn-default" type="submit">Eintragen</button>
                    </span>
            </div>
        </form>
        <div v-if="queue.iAmOnList" class="col-md-2">
            <div class="label label-success" v-if="subqueue.iAmOnList">Du</div>
        </div>
    </div>
</div>


<?php
$html        = ob_get_clean();
$registerUrl = UrlHelper::createUrl('speech/register');
?>

<script>
    Vue.component('speech-user-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user'],
        data() {
            //console.log(JSON.parse(JSON.stringify(this.queue)));
            return {
                registerName: this.user.name
            };
        },
        computed: {},
        methods: {
            register: function ($event, subqueue) {
                $event.preventDefault();

                $.post(<?= json_encode($registerUrl) ?>, {
                    queue: this.queue.id,
                    subqueue: subqueue.id,
                    username: this.registerName,
                    _csrf: this.csrf,
                }, (data) => {
                    if (!data['success']) {
                        alert(data['message']);
                        return;
                    }

                    this.queue = data['queue'];
                }).catch(err => {
                    alert(err.responseText);
                });
            }
        },
    });
</script>
