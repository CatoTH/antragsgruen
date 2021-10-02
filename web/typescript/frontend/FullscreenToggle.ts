import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class FullscreenToggle {
    private readonly element: HTMLElement;
    private readonly holderElement: HTMLElement;
    private readonly vueElement: string = null;
    private vueWidget = null;

    constructor(private $element: JQuery) {
        this.element = $element[0] as HTMLElement;
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

    private requestFullscreen() {
        if (this.vueElement) {
            document.querySelector("body").append(this.holderElement);
        }

        let holderElement = this.holderElement as any;
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

    private exitFullscreen() {
        let doc: any = document;
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

    private isFullscreen(): boolean {
        let doc: any = document;
        return !!(doc.fullscreenElement ||
            doc.webkitFullscreenElement ||
            doc.mozFullScreenElement ||
            doc.msFullscreenElement);
    }

    private toggleFullScreeen() {
        if (this.isFullscreen()) {
            this.exitFullscreen();
        } else {
            this.requestFullscreen();
        }
    }

    private onFullscreenChange() {
        if (!this.isFullscreen() && this.vueElement) {
            this.destroyVueElement();
            this.holderElement.remove();
        }
    }

    private createFullscreenVueHolder(): HTMLElement
    {
        const element = document.createElement('div');
        const vueHolder = document.createElement('div');
        element.append(vueHolder);

        return element;
    }

    private initVueElement(): void
    {
        let template = '<' + this.vueElement + ' :initdata="initdata"></' + this.vueElement + '>';
        let initdata = {};
        if (this.element.getAttribute('data-vue-initdata')) {
            initdata = JSON.parse(this.element.getAttribute('data-vue-initdata'));
        }
        this.vueWidget = new Vue({
            el: this.holderElement.firstChild as HTMLElement,
            template,
            data() {
                return {
                    initdata
                };
            },
            methods: {},
            beforeDestroy() {},
            created() {}
        });
    }

    private destroyVueElement(): void
    {
        this.vueWidget.$destroy();
    }
}
