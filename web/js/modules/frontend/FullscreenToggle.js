// @ts-check

import { createApp, h } from '/npm/vue.esm-browser.prod.js';
import translateDirective from "/js/vue/Translate.vue.js";
import fullscreenProjectorComponent from "/js/vue/fullscreen/FullscreenProjector.js";
import fullscreenIMotionComponent from "/js/vue/fullscreen/FullscreenIMotion.js";
import fullscreenPanelComponent from "/js/vue/fullscreen/FullscreenPanel.js";
import fullscreenSpeechComponent from "/js/vue/speech/FullscreenSpeech.js";

export class FullscreenToggle {
    /** @type {HTMLElement} */ element;
    /** @type {HTMLElement} */ holderElement;
    /** @type {string|null} */  vueElement;
    /** @type {import('vue').App|null} */ vueWidget = null;
    translations;

    constructor(element, translations) {
        this.element = element;
        if (this.element.getAttribute('data-vue-element')) {
            this.vueElement = this.element.getAttribute('data-vue-element');
            this.holderElement = this.createFullscreenVueHolder();
        } else {
            this.holderElement = document.querySelector(".well");
        }
        this.element.addEventListener('click', this.toggleFullScreeen.bind(this));
        this.translations = translations;

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
        let template = '<fullscreen-projector :initdata="initdata" @close="close" @changed="changed"></fullscreen-projector>';
        let initdata = {};
        if (this.element.getAttribute('data-vue-initdata')) {
            initdata = JSON.parse(this.element.getAttribute('data-vue-initdata'));
        }
        this.vueWidget = createApp({
            template,
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

        Object.keys(this.translations).forEach( category => {
            translateDirective.registerTranslation(category, this.translations[category]);
        });
        this.vueWidget.directive('t', translateDirective);

        this.vueWidget.component('fullscreen-projector', fullscreenProjectorComponent);
        this.vueWidget.component('fullscreen-panel', fullscreenPanelComponent);
        this.vueWidget.component('fullscreen-imotion', fullscreenIMotionComponent);
        this.vueWidget.component('fullscreen-speech', fullscreenSpeechComponent);

        this.vueWidget.mount(this.holderElement.firstChild);
    }

    destroyVueElement() {
        this.vueWidget.unmount();
    }
}
