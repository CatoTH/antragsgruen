export class DraftSavingEngine {
    private $html: JQuery;
    private localKey: string;
    private isChanged: boolean = false;

    constructor(private $form: JQuery, private $draftHint: JQuery, keyBase: string) {
        this.$html = $('html');

        if (!this.testLocalstorageEnabled()) {
            return;
        }

        this.localKey = keyBase + "_" + Math.floor(Math.random() * 1000000);

        let key;

        $form.append('<input type="hidden" name="draftId" value="' + this.localKey + '">');

        for (key in localStorage) if (localStorage.hasOwnProperty(key)) {
            if (key.indexOf(keyBase + "_") == 0) {
                let data = JSON.parse(localStorage.getItem(key)),
                    lastEdit = new Date(data['lastEdit']),
                    $link = $("<li><button type='button' class='btn-link restore'></button> " +
                        "<button type='button' class='btn-link delete' title='" + __t("std", "draft_del") + "' aria-label='" + __t("std", "draft_del") + "'>" +
                        "<span class='glyphicon glyphicon-trash' aria-hidden='true'></button>" +
                        "</li>");


                $link.data("key", key);
                let dateStr = new Intl.DateTimeFormat(this.$html.attr("lang"), {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    hour: 'numeric', minute: 'numeric'
                }).format(lastEdit);
                $link.find('.restore').text(__t("std", "draft_date") + ': ' + dateStr).on("click", (ev) => {
                    ev.preventDefault();
                    let $li = $(ev.delegateTarget).parents("li").first();
                    bootbox.confirm(__t("std", "draft_restore_confirm"), (result) => {
                        if (result) {
                            this.doRestore($li);
                        }
                    });
                });
                $link.find('.delete').on("click", (ev) => {
                    ev.preventDefault();
                    let $li = $(ev.delegateTarget).parents("li").first();
                    bootbox.confirm(__t("std", "draft_del_confirm"), (result) => {
                        if (result) {
                            this.doDelete($li);
                        }
                    });
                });
                this.$draftHint.find("ul").append($link);
                this.$draftHint.removeClass("hidden");
            }
        }

        window.setTimeout(this.saveInitialData.bind(this), 2000);

        window.setInterval(this.doBackup.bind(this), 3000);
    }

    private testLocalstorageEnabled(): boolean {
        try {
            const key = `__storage__test`;
            window.localStorage.setItem(key, null);
            window.localStorage.removeItem(key);
            return true;
        } catch (e) {
            return false;
        }
    }

    private saveInitialData() {
        for (let inst in CKEDITOR.instances) {
            if (CKEDITOR.instances.hasOwnProperty(inst)) {
                $("#" + inst).data("original", CKEDITOR.instances[inst].getData());
            }
        }
        $(".form-group.plain-text").each(function () {
            let $input = $(this).find("input[type=text]");
            $input.data("original", $input.val());
        });
        $(".form-group.amendmentStatus").each(function () {
            let $input = $(this).find("input[type=text].hidden");
            $input.data("original", $input.val());
        });
    }

    private doBackup() {
        let data = {},
            foundChanged = false,
            inst;

        for (inst in CKEDITOR.instances) {
            if (CKEDITOR.instances.hasOwnProperty(inst)) {
                let dat = CKEDITOR.instances[inst].getData();
                data[inst] = dat;
                if (dat != $("#" + inst).data("original")) {
                    foundChanged = true;
                }
            }
        }
        $(".form-group.plain-text").each(function () {
            let $input = $(this).find("input[type=text]");
            data[$input.attr("id")] = $input.val();
            if ($input.val() != $input.data("original")) {
                foundChanged = true;
            }
        });
        $(".form-group.amendmentStatus").each(function () {
            let $input = $(this).find("input[type=text].hidden"),
                id = $(this).find(".stdDropdown").attr("id");
            data[id] = $input.val();
            if ($input.val() != $input.data("original")) {
                foundChanged = true;
            }
        });

        if (foundChanged) {
            data['lastEdit'] = new Date().getTime();
            localStorage.setItem(this.localKey, JSON.stringify(data));
            this.isChanged = true;
        } else {
            localStorage.removeItem(this.localKey);
            this.isChanged = false;
        }
    }

    private doDelete($li: JQuery) {
        localStorage.removeItem($li.data("key"));
        $li.remove();
        if (this.$draftHint.find("ul").children().length == 0) {
            this.$draftHint.addClass("hidden");
        }
    }

    private doRestore($li: JQuery) {
        let inst,
            restoreKey = $li.data("key"),
            data = JSON.parse(localStorage.getItem(restoreKey));

        for (inst in CKEDITOR.instances) {
            if (CKEDITOR.instances.hasOwnProperty(inst)) {
                if (typeof(data[inst]) != "undefined") {
                    CKEDITOR.instances[inst].setData(data[inst]);
                }
            }
        }
        if (data.hasOwnProperty("amendmentEditorial_wysiwyg") && data['amendmentEditorial_wysiwyg'] != '') {
            if (!CKEDITOR.hasOwnProperty('amendmentEditorial_wysiwyg')) {
                $(".editorialChange .opener").trigger("click");
                window.setTimeout(function () {
                    CKEDITOR.instances['amendmentEditorial_wysiwyg'].setData(data['amendmentEditorial_wysiwyg']);
                }, 100);
            }
        }

        $(".form-group.plain-text").each((i, el) => {
            let $input = $(el).find("input[type=text]");
            if (typeof(data[$input.attr("id")]) != "undefined") {
                $input.val(data[$input.attr("id")]);
            }
        });
        $(".form-group.amendmentStatus").each((i, el) => {
            let id = $(el).find(".stdDropdown").attr("id");
            if (typeof(data[id]) != "undefined") {
                $('#' + id).val(data[id]);
            }
        });

        this.$form.find("input[name=draftId]").remove();
        this.$form.append('<input type="hidden" name="draftId" value="' + restoreKey + '">');

        this.localKey = restoreKey;
        $li.remove();
        if (this.$draftHint.find("ul").children().length == 0) {
            this.$draftHint.addClass("hidden");
        }
    }

    public hasChanges(): boolean {
        return this.isChanged;
    }
}
