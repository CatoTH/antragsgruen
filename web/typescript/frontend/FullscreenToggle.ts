declare let Vue: any;

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
            const newUrl = (this.vueWidget.currIMotion ? this.vueWidget.currIMotion.url_html : null);
            this.destroyVueElement();
            this.holderElement.remove();
            if (newUrl && window.location.href !== newUrl) {
                window.location.href = newUrl;
            }
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
        const widget = this;
        let template = '<' + this.vueElement + ' :initdata="initdata" @close="close" @changed="changed"></' + this.vueElement + '>';
        let initdata = {};
        if (this.element.getAttribute('data-vue-initdata')) {
            initdata = JSON.parse(this.element.getAttribute('data-vue-initdata'));
        }
        this.vueWidget = Vue.createApp({
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
            beforeUnmount() {},
            created() {}
        });

        this.vueWidget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.vueWidget, 'fullscreen');

        this.vueWidget.mount(this.holderElement.firstChild);
    }

    private destroyVueElement(): void
    {
        this.vueWidget.unmount();
    }
}
