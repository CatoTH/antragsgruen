// @ts-check

import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
import translateDirective from "/js/vue/Translate.vue.js";
import fullscreenProjectorComponent from "/js/vue/fullscreen/FullscreenProjector.js";
import fullscreenIMotionComponent from "/js/vue/fullscreen/FullscreenIMotion.js";
import fullscreenPanelComponent from "/js/vue/fullscreen/FullscreenPanel.js";
import fullscreenSpeechComponent from "/js/vue/speech/FullscreenSpeech.js";
import currentDebateWidgetComponent from "/js/vue/debate/CurrentDebateWidget.js";
import { getSpeechCommonMixins } from "/js/vue/speech/SpeechCommonMixins.js";
import userInlineWidgetComponent from "/js/vue/speech/UserInlineWidget.js";
import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";
import votingBlockWidgetComponent from "/js/vue/voting/VotingBlockWidget.js";
import voteListComponent from "/js/vue/voting/VotingList.js";

export class FullscreenToggle {
    /** @type {HTMLElement} */ element;
    /** @type {HTMLElement} */ holderElement;
    /** @type {string|null} */  vueElement;
    /** @type {import('vue').App|null} */ vueWidget = null;

    constructor(element) {
        this.element = element;
        if (this.element.getAttribute('data-vue-element')) {
            this.vueElement = this.element.getAttribute('data-vue-element');
            this.holderElement = this.createFullscreenVueHolder();
        } else {
            this.holderElement = document.querySelector(".well");
        }
        this.element.addEventListener('click', this.toggleFullScreeen.bind(this));

        ["fullscreenchange", "webkitfullscreenchange", "mozfullscreenchange", "msfullscreenchange"].forEach(
            eventType => document.addEventListener(eventType, this.onFullscreenChange.bind(this), false)
        );
    }

    requestFullscreen() {
        if (this.vueElement) {
            document.querySelector("body").append(this.holderElement);
        }

        let holderElement = this.holderElement;
        if (holderElement.requestFullscreen) {
            holderElement.requestFullscreen();
        } else if (holderElement.webkitRequestFullscreen) {
            holderElement.webkitRequestFullscreen();
        } else if (holderElement.mozRequestFullScreen) {
            holderElement.mozRequestFullScreen();
        } else if (holderElement.msRequestFullscreen) {
            holderElement.msRequestFullscreen();
        }

        if (this.vueElement) {
            this.initVueElement();
        }
    }

    exitFullscreen() {
        /** @type {HTMLDocument} */
        let doc = document;
        if (doc.exitFullscreen) {
            doc.exitFullscreen();
        } else if (doc.webkitExitFullscreen) {
            doc.webkitExitFullscreen();
        } else if (doc.mozCancelFullScreen) {
            doc.mozCancelFullScreen();
        } else if (doc.msExitFullscreen) {
            doc.msExitFullscreen();
        }
    }

    isFullscreen() {
        let doc = document;
        return !!(doc.fullscreenElement ||
            doc.webkitFullscreenElement ||
            doc.mozFullScreenElement ||
            doc.msFullscreenElement);
    }

    toggleFullScreeen() {
        if (this.isFullscreen()) {
            this.exitFullscreen();
        } else {
            this.requestFullscreen();
        }
    }

    onFullscreenChange() {
        if (!this.isFullscreen() && this.vueElement) {
            const newUrl = (this.vueWidget.currIMotion ? this.vueWidget.currIMotion.url_html : null);
            this.destroyVueElement();
            this.holderElement.remove();
            if (newUrl && window.location.href !== newUrl) {
                window.location.href = newUrl;
            }
        }
    }

    createFullscreenVueHolder() {
        const element = document.createElement('div');
        const vueHolder = document.createElement('div');
        element.append(vueHolder);

        return element;
    }

    initVueElement() {
        const widget = this;
        let initdata = {};
        if (this.element.getAttribute('data-vue-initdata')) {
            initdata = JSON.parse(this.element.getAttribute('data-vue-initdata'));
        }
        this.vueWidget = createApp({
            render() {
                return h(resolveComponent('fullscreen-projector'), {
                    initdata: this.initdata,
                    onClose: (newUrl) => this.close(newUrl),
                    onChanged: (newIMotion) => this.changed(newIMotion),
                });
            },
            data() {
                return {
                    initdata,
                    currIMotion: null
                };
            },
            methods: {
                close: function (newUrl) {
                    if (widget.isFullscreen()) {
                        widget.exitFullscreen();
                    } else {
                        widget.destroyVueElement();
                        widget.holderElement.remove();
                    }
                    if (newUrl && newUrl !== window.location.href) {
                        window.location.href = newUrl;
                    }
                },
                changed: function (newIMotion) {
                    this.currIMotion = newIMotion;
                }
            },
            beforeUnmount() {
            },
            created() {
            }
        });

        this.vueWidget.directive('t', translateDirective);

        this.vueWidget.component('fullscreen-projector', fullscreenProjectorComponent);
        this.vueWidget.component('fullscreen-panel', fullscreenPanelComponent);
        this.vueWidget.component('fullscreen-imotion', fullscreenIMotionComponent);
        this.vueWidget.component('fullscreen-speech', fullscreenSpeechComponent);

        // The "Currently debated" widget is offered as a projector option when the feature is enabled.
        // It renders the read-only user widget, reusing the same speech/voting child components as the
        // inline homepage widget. The speech and voting common mixins share method names, so they are
        // applied per-component instead of globally to keep them from colliding in this shared app.
        if (initdata.debate) {
            const speechMixins = getSpeechCommonMixins();
            const votingMixins = getVotingCommonMixins(initdata.debate.voting_constants);
            this.vueWidget.component('current-debate-widget', currentDebateWidgetComponent);
            this.vueWidget.component('speech-user-inline-widget', { ...userInlineWidgetComponent, mixins: [speechMixins] });
            this.vueWidget.component('voting-block-widget', { ...votingBlockWidgetComponent, mixins: [votingMixins] });
            this.vueWidget.component('vote-list', { ...voteListComponent, mixins: [votingMixins] });
        }

        this.vueWidget.mount(this.holderElement.firstChild);
    }

    destroyVueElement() {
        this.vueWidget.unmount();
    }
}
