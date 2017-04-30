interface WizardState {
    wording: number;
    singleMotion: number;
    motionsInitiatedBy: number;
    motionsDeadlineExists: number;
    motionsDeadline: string;
    motionScreening: number;
    needsSupporters: number;
    minSupporters: number;
    hasAmendments: number;
    amendSinglePara: number;
    amendMerging: number;
    amendmentInitiatedBy: number;
    amendmentDeadlineExists: number;
    amendmentDeadline: string;
    amendScreening: number;
    hasComments: number;
    hasAgenda: number;
    openNow: number;
    title: string;
    organization: string;
    subdomain: string;
    contact: string;
}

class SiteCreateWizard {
    private firstPanel: string = "#panelPurpose";
    private mode: string;
    private data: WizardState;
    private $activePanel: JQuery;

    constructor(private $root: JQuery) {
        this.firstPanel = "#panelPurpose";
        this.mode = $("#SiteCreateWizard").data("mode");
        this.initEvents();
    }

    getRadioValue(fieldsetClass: string, defaultVal: any): any {
        let $input = this.$root.find("fieldset." + fieldsetClass).find("input:checked");
        if ($input.length > 0) {
            return $input.val();
        } else {
            return defaultVal;
        }
    };

    getWizardState(): WizardState {
        return {
            wording: this.getRadioValue('wording', 1),
            singleMotion: this.getRadioValue('singleMotion', 0),
            motionsInitiatedBy: this.getRadioValue('motionWho', 1),
            motionsDeadlineExists: this.getRadioValue('motionDeadline', 0),
            motionsDeadline: this.$root.find("fieldset.motionDeadline .date input").val(),
            motionScreening: this.getRadioValue('motionScreening', 1),
            needsSupporters: this.getRadioValue('needsSupporters', 0),
            minSupporters: this.$root.find("input.minSupporters").val(),
            hasAmendments: this.getRadioValue('hasAmendments', 1),
            amendSinglePara: this.getRadioValue('amendSinglePara', 0),
            amendMerging: this.getRadioValue('amendMerging', 0),
            amendmentInitiatedBy: this.getRadioValue('amendmentWho', 1),
            amendmentDeadlineExists: this.getRadioValue('amendmentDeadline', 0),
            amendmentDeadline: this.$root.find("fieldset.amendmentDeadline .date input").val(),
            amendScreening: this.getRadioValue('amendScreening', 1),
            hasComments: this.getRadioValue('hasComments', 1),
            hasAgenda: this.getRadioValue('hasAgenda', 0),
            openNow: this.getRadioValue('openNow', 0),
            title: $("#siteTitle").val(),
            organization: $("#siteOrganization").val(),
            subdomain: $("#siteSubdomain").val(),
            contact: $("#siteContact").val()
        };
    };

    showPanel($panel: JQuery) {
        this.data = this.getWizardState();

        let step = $panel.data("tab");
        this.$root.find(".wizard .steps li").removeClass("active");
        this.$root.find(".wizard .steps ." + step).addClass("active");

        if (this.$activePanel) {
            this.$activePanel.removeClass("active").addClass("inactive");
        }
        $panel.addClass("active").removeClass("inactive");
        this.$activePanel = $panel;

        try {
            let isCorrect = (window.location.hash == "#" + $panel.attr("id"));
            if ((window.location.hash == "" || window.location.hash == "#") && "#" + $panel.attr("id") == this.firstPanel) {
                isCorrect = true;
            }
            if (!isCorrect) {
                window.location.hash = "#" + $panel.attr("id").substring(5);
            }
        } catch (e) {
            console.log(e);
        }
    };

    getNextPanel(): string {
        this.data = this.getWizardState();

        switch (this.$activePanel.attr("id")) {
            case 'panelPurpose':
                return "#panelSingleMotion";
            case 'panelSingleMotion':
                if (this.data.singleMotion == 1) {
                    return "#panelHasAmendments";
                } else {
                    return "#panelMotionWho";
                }
            case 'panelMotionWho':
                if (this.data.motionsInitiatedBy == 1) { // MOTION_INITIATED_ADMINS
                    return "#panelHasAmendments";
                } else {
                    return "#panelMotionDeadline";
                }
            case 'panelMotionDeadline':
                return "#panelMotionScreening";
            case 'panelMotionScreening':
                return "#panelNeedsSupporters";
            case 'panelNeedsSupporters':
                return "#panelHasAmendments";
            case 'panelHasAmendments':
                if (this.data.hasAmendments == 1) {
                    return "#panelAmendSinglePara";
                } else {
                    return "#panelComments";
                }
            case 'panelAmendSinglePara':
                return "#panelAmendWho";
            case 'panelAmendWho':
                if (this.data.amendmentInitiatedBy == 1) { // MOTION_INITIATED_ADMINS
                    return "#panelComments";
                } else {
                    return "#panelAmendDeadline";
                }
            case 'panelAmendDeadline':
                return '#panelAmendMerging';
            case 'panelAmendMerging':
                return "#panelAmendScreening";
            case 'panelAmendScreening':
                return "#panelComments";
            case 'panelComments':
                if (this.data.singleMotion == 1) {
                    return "#panelOpenNow";
                } else {
                    return "#panelAgenda";
                }
            case 'panelAgenda':
                return "#panelOpenNow";
            case 'panelOpenNow':
                return "#panelSiteData";
        }
    }

    subdomainChange(ev) {
        let $this = $(ev.currentTarget),
            subdomain = $this.val(),
            $group = $this.parents(".subdomainRow").first(),
            requesturl = $this.data("query-url").replace(/SUBDOMAIN/, subdomain),
            $err = $group.find(".subdomainError");

        if (subdomain == "") {
            $err.addClass("hidden");
            $group.removeClass("has-error").removeClass("has-success");
            return;
        }
        if (!subdomain.match(/^[A-Z0-9äöü](?:[A-Z0-9äöü_\-]{0,61}[A-Z0-9äöü])?$/i)) {
            $group.removeClass("has-success").addClass("has-error");
            this.$root.find("button[type=submit]").prop("disabled", true);
            return;
        }
        $.get(requesturl, (ret) => {
            if (ret['available']) {
                $err.addClass("hidden");
                $group.removeClass("has-error");
                this.$root.find("button[type=submit]").prop("disabled", false);
                if (ret['subdomain'] == $this.val()) {
                    $group.addClass("has-success");
                }
            } else {
                $err.removeClass("hidden");
                $err.html($err.data("template").replace(/%SUBDOMAIN%/, ret['subdomain']));
                $group.removeClass("has-success");
                if (ret['subdomain'] == $this.val()) {
                    this.$root.find("button[type=submit]").prop("disabled", true);
                    $group.addClass("has-error");
                }
            }
        });
    }

    initEvents() {
        let $form = this.$root;

        this.$activePanel = null;
        this.data = this.getWizardState();

        $form.find("input").change(() => {
            this.data = this.getWizardState();
        });
        $form.find(".radio-label input").change(function () {
            let $fieldset = $(this).parents("fieldset").first();
            $fieldset.find(".radio-label").removeClass("active");
            let $active = $fieldset.find(".radio-label input:checked");
            $active.parents(".radio-label").first().addClass("active");
        }).trigger("change");

        $form.find("fieldset.wording input").change(function () {
            let wording = $form.find("fieldset.wording input:checked").data("wording-name");
            $form.removeClass("wording_motion").removeClass("wording_manifesto").addClass("wording_" + wording);
        }).trigger("change");

        $form.find(".input-group.date").each(function () {
            let $this = $(this);
            $this.datetimepicker({
                locale: $this.find("input").data('locale')
            });
        });
        $form.find(".date.motionsDeadline").on("dp.change", function () {
            $("input.motionsDeadlineExists").prop("checked", true).change();
        });
        $form.find(".date.amendmentDeadline").on("dp.change", function () {
            $("input.amendDeadlineExists").prop("checked", true).change();
        });
        $form.find("input.minSupporters").change(function () {
            $("input.needsSupporters").prop("checked", true).change();
        });
        $form.find("#siteSubdomain").on("keyup change", this.subdomainChange.bind(this));
        $form.find("#siteTitle").on("keyup change", function () {
            if ($(this).val().length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });
        $form.find("#siteOrganization").on("keyup change", function () {
            if ($(this).val().length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });

        $form.find("#panelSiteData input").on("keypress", function (ev) {
            let original: any = ev.originalEvent;
            if (original.charCode == 13) {
                ev.preventDefault();
            }
        });

        let obj = this;
        $form.find(".navigation .btn-next").click(function (ev) {
            if ($(this).attr("type") == "submit") {
                return;
            }
            ev.preventDefault();
            obj.showPanel($(obj.getNextPanel()));
        });
        $form.find(".navigation .btn-prev").click(function (ev) {
            ev.preventDefault();
            if (window.location.hash != "") {
                window.history.back();
            }
        });

        $(window).on("hashchange", (ev) => {
            ev.preventDefault();
            let hash;
            if (parseInt(window.location.hash.substring(1)) === 0) {
                hash = this.firstPanel;
            } else {
                hash = "#panel" + window.location.hash.substring(1);
            }
            let $panel = $(hash);
            if ($panel.length > 0) {
                this.showPanel($panel);
            }
        });

        $form.find(".step-pane").addClass("inactive");
        this.showPanel($(this.firstPanel));
    }
}

