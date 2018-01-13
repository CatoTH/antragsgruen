export class FullscreenToggle {
    private $holderElement: JQuery;

    constructor(private $element: JQuery) {
        this.$holderElement = $(".well").first();
        this.$element.click(this.toggleFullScreeen.bind(this));
    }

    private requestFullscreen() {
        let holderElement: any = this.$holderElement[0];
        if (holderElement.requestFullscreen) {
            holderElement.requestFullscreen();
        } else if (holderElement.webkitRequestFullscreen) {
            holderElement.webkitRequestFullscreen();
        } else if (holderElement.mozRequestFullScreen) {
            holderElement.mozRequestFullScreen();
        } else if (holderElement.msRequestFullscreen) {
            holderElement.msRequestFullscreen();
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
        return doc.fullscreenElement ||
            doc.webkitFullscreenElement ||
            doc.mozFullScreenElement ||
            doc.msFullscreenElement;
    }

    private toggleFullScreeen() {
        if (this.isFullscreen()) {
            this.exitFullscreen();
        } else {
            this.requestFullscreen();
        }
    }
}
