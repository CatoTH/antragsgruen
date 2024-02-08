import domObject = CKEDITOR.dom.domObject;
import editor = CKEDITOR.editor;

export class AntragsgruenEditor {
    private editor: editor;
    private $el: JQuery;

    private static ckeditor_strip(html: string): string {
        let tmp = document.createElement("div");
        tmp.innerHTML = html;

        if (tmp.textContent == '' && typeof tmp.innerText == 'undefined') {
            return '';
        }

        return tmp.textContent || tmp.innerText;
    }

    private static ckeditor_charcount(text: string): number {
        let normalizedText = text.replace(/(\r\n|\n|\r)/gm, "").replace(/^\s+|\s+$/g, "").replace("&nbsp;", "");
        normalizedText = AntragsgruenEditor.ckeditor_strip(normalizedText).replace(/^([\s\t\r\n]*)$/, "");

        return normalizedText.length;
    }

    private $currCounter: JQuery;
    private $warning: JQuery;
    private $submit: JQuery;
    private maxLen: number;
    private maxLenSoft: boolean;

    private maxLenOnChange() {
        let currLen = AntragsgruenEditor.ckeditor_charcount(this.editor.getData());
        this.$currCounter.text(currLen);
        if (currLen > this.maxLen) {
            this.$warning.removeClass('hidden');
            if (!this.maxLenSoft) {
                this.$submit.prop("disabled", true);
            }
        } else {
            this.$warning.addClass('hidden');
            if (!this.maxLenSoft) {
                this.$submit.prop("disabled", false);
            }
        }
    }

    private initMaxLen() {
        let $fieldset = this.$el.parents(".wysiwyg-textarea").first();
        if (!$fieldset.data("max-len")) {
            return;
        }

        this.maxLen = $fieldset.data("max-len");
        this.maxLenSoft = false;
        this.$warning = $fieldset.find('.maxLenTooLong');
        this.$submit = this.$el.parents("form").first().find("button[type=submit]");
        this.$currCounter = $fieldset.find(".maxLenHint .counter");

        if (this.maxLen < 0) {
            this.maxLenSoft = true;
            this.maxLen = -1 * this.maxLen;
        }

        this.editor.on('change', this.maxLenOnChange.bind(this));
        this.maxLenOnChange();
    }

    private static createConfig(title: string, noStrike: boolean, trackChanged: boolean, allowDiffFormattings: boolean, autocolorize: boolean, enterMode: any): any {
        let ckeditorConfig = {
            versionCheck: false,
            coreStyles_strike: {
                element: 'span',
                attributes: {'class': 'strike'},
                overrides: 'strike'
            },
            coreStyles_underline: {
                element: 'span',
                attributes: {'class': 'underline'}
            },
            toolbarGroups: [
                {name: 'tools'},
                {name: 'document', groups: ['mode', 'document', 'doctools']},
                //{name: 'clipboard', groups: ['clipboard', 'undo']},
                //{name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                //{name: 'forms'},
                {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
                {name: 'links'},
                {name: 'insert'},
                {name: 'styles'},
                {name: 'colors'},
                {name: 'autocolorize'},
                {name: 'others'}
            ],
            removePlugins: 'stylescombo,save,showblocks,specialchar,about,preview,pastetext,magicline,liststyle',
            extraPlugins: 'tabletools,listitemstyle',
            scayt_sLang: 'de_DE',
            title: title,
            enterMode: enterMode,
            shiftEnterMode: (enterMode === CKEDITOR.ENTER_BR ? CKEDITOR.ENTER_P : CKEDITOR.ENTER_BR),
            linkDefaultProtocol: 'https://',
        };

        let strikeEl = (noStrike ? '' : ' s'),
            strikeClass = (noStrike ? '' : ',strike'),
            autocolorizeClass = (autocolorize ? ',adminTyped1,adminTyped2' : ''),
            allowedContent = '';

        if (trackChanged || allowDiffFormattings) {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'h1 h2 h3 h4(ice-ins,ice-del,ice-cts,appendHint,appendedCollision,moved);' +
                'ol[start,data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision,moved,decimalDot,decimalCircle,lowerAlpha,upperAlpha);' +
                'li[value,data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision,moved);' +
                'ul[data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision,moved);' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'div [data-*](collidingParagraph,hasCollisions,moved);' +
                'p blockquote [data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision,collidingParagraphHead,moved){border,margin,padding};' +
                'span[data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision,underline' + strikeClass + ',subscript,superscript' + autocolorizeClass + ');' +
                'a[href,data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision);' +
                'br ins del[data-*](ice-ins,ice-del,ice-cts,appendHint,appendedCollision);';
        } else {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'ul;' +
                'ol[start](decimalDot,decimalCircle,lowerAlpha,upperAlpha);' +
                'li[value];' +
                'h2 h3 h4;' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline' + strikeClass + ',subscript,superscript' + autocolorizeClass + ');' +
                'a[href];';
        }

        if (trackChanged) {
            ckeditorConfig.extraPlugins += ',lite';
            ckeditorConfig['lite'] = {tooltips: false};

            // Undo and Track changes are incompatible
            // https://github.com/CatoTH/antragsgruen/issues/224
            // http://dev.ckeditor.com/ticket/14854
            ckeditorConfig['removePlugins'] += ',undo';
        } else {
            ckeditorConfig['removePlugins'] += ',lite';
        }
        if (autocolorize) {
            ckeditorConfig['extraPlugins'] += ',autocolorize';
        }
        ckeditorConfig['allowedContent'] = allowedContent;
        // ckeditorConfig.pasteFilter = allowedContent; // Seems to break copy/pasting some <strong> formatting in 4.5.11

        return ckeditorConfig
    }

    public getEditor(): editor {
        return this.editor;
    }

    public static destroyInstanceById(id: string) {
        let editor = CKEDITOR.instances[id];
        editor.destroy();
        let $el: JQuery = $("#" + id);
        $el.data("ckeditor_initialized", "0");
        $el.attr("contenteditable", "false");
    }

    constructor(id) {
        this.$el = $("#" + id);

        let initialized = this.$el.data("ckeditor_initialized");
        if (typeof (initialized) != "undefined" && initialized == "1") {
            console.error("Already initialized: " + id);
            return;
        }

        this.$el.data("ckeditor_initialized", "1");
        this.$el.attr("contenteditable", "true");

        let ckeditorConfig = AntragsgruenEditor.createConfig(
            this.$el.attr("title"),
            (this.$el.data("no-strike") == '1'),
            (this.$el.data('track-changed') == '1'),
            (this.$el.data('allow-diff-formattings') == '1'),
            (this.$el.data('autocolorize') == '1'),
            (this.$el.data('enter-mode') == 'br' ? CKEDITOR.ENTER_BR : CKEDITOR.ENTER_P)
        );
        ckeditorConfig.versionCheck = false;

        // This prevents strange behavior in chrome: after switching from the WYSIWYG editor field to a regular input,
        // the focus of the new input field was lost after 200ms
        let focusManager: any = CKEDITOR.focusManager;
        focusManager._.blurDelay = 0;

        this.editor = CKEDITOR.inline(id, ckeditorConfig);

        this.initMaxLen();
    }
}
