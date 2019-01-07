<template>
    <div class="btn-group selectlist full-size" data-resize="auto" data-initialize="selectlist">
        <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            <span class="selected-label">- egal -</span>
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li v-for="item in items"
                v-bind:data-num="item.num"
                v-bind:data-title="item.title"
                v-bind:data-value="item.id"><a href="#">
                {{ item.title }}
                <span class="num" v-if="item.num !== null">({{ item.num }})</span>
            </a></li>
        </ul>
        <input class="hidden hidden-field" readonly="readonly" title="[Hidden]" aria-hidden="true" type="text" value="">
    </div>
</template>

<script lang="ts">
    import {Vue, Component, Prop} from "vue-property-decorator";

    declare var $: any;

    export interface SelectlistItem {
        id: string;
        title: string;
        num: number;
    }

    /**
     * Basically a wrapper for fuelux. Not exactly elegant, but works...
     */
    @Component
    export default class SelectList extends Vue {
        @Prop() items!: SelectlistItem[];
        @Prop() selected!: string;

        public created() {
            this.initSelectlistWrapper();
        }

        private initSelectlistWrapper() {
            if ($.fn.selectlist) {
                this.initSelectlist();
            } else {
                window.setTimeout(() => {
                    this.initSelectlist();
                }, 100);
            }
        }

        private initSelectlist() {
            const $el = $(this.$el);
            $el.selectlist();
            $el.on('changed.fu.selectlist', (ev, item) => {
                this.$emit('selected', {
                    id: item.value.toString(),
                    title: item.title,
                    num: parseInt(item.num),
                });
            });
        }
    }
</script>
