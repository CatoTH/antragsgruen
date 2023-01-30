interface WizardState {
    language: string;
    functionality: number[];
    singleMotion: number;
    motionsInitiatedBy: number;
    motionsDeadlineExists: number;
    motionsDeadline: string;
    motionScreening: number;
    needsSupporters: number;
    minSupporters: number;
    hasAmendments: number;
    amendSinglePara: number;
    amendmentInitiatedBy: number;
    amendmentDeadlineExists: number;
    amendmentDeadline: string;
    amendScreening: number;
    hasComments: number;
    applicationType: number;
    speechQuotas: number;
    speechLogin: number;
    openNow: number;
    title: string;
    organization: string;
    subdomain: string;
    contact: string;
}

// Sync with SiteCreateForm.php
const FUNCTIONALITY_MOTIONS = 1;
const FUNCTIONALITY_MANIFESTO = 2;
const FUNCTIONALITY_APPLICATIONS = 3;
const FUNCTIONALITY_AGENDA = 4;
const FUNCTIONALITY_SPEECH_LISTS = 5;
const FUNCTIONALITY_STATUTE_AMENDMENTS = 6;

class SiteCreateWizard {
    private firstPanel: string;
    private mode: string;
    private data: WizardState;
    private $activePanel: JQuery;

    constructor(private $root: JQuery) {
        this.firstPanel = $("#SiteCreateWizard").data("init-step");
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
    }

    getCheckboxValues(fieldsetClass: string, defaultVals: any): any {
        let inputs = this.$root.find("fieldset." + fieldsetClass).find("input:checked").toArray();
        if (inputs.length > 0) {
            return inputs.map((element: HTMLElement) => {
                return parseInt(element.getAttribute('value'), 10);
            });
        } else {
            return defaultVals;
        }
    }

    getWizardState(): WizardState {
        const parseNullableNumber = (val: string): number => {
            if (val === '' || val === null) {
                return null;
            } else {
                return parseInt(val, 10);
            }
        };

        return {
            language: this.getRadioValue('language', null),
            functionality: this.getCheckboxValues('functionality', []),
            singleMotion: parseInt(this.getRadioValue('singleMotion', 0), 10),
            motionsInitiatedBy: parseInt(this.getRadioValue('motionWho', 1), 10),
            motionsDeadlineExists: parseInt(this.getRadioValue('motionDeadline', 0), 10),
            motionsDeadline: this.$root.find("fieldset.motionDeadline .date input").val() as string,
            motionScreening: parseInt(this.getRadioValue('motionScreening', 1), 10),
            needsSupporters: parseInt(this.getRadioValue('needsSupporters', 0), 10),
            minSupporters: parseNullableNumber(this.$root.find("input.minSupporters").val() as string),
            hasAmendments: parseInt(this.getRadioValue('hasAmendments', 1), 10),
            amendSinglePara: parseInt(this.getRadioValue('amendSinglePara', 0), 10),
            amendmentInitiatedBy: parseInt(this.getRadioValue('amendmentWho', 1), 10),
            amendmentDeadlineExists: parseInt(this.getRadioValue('amendmentDeadline', 0), 10),
            amendmentDeadline: this.$root.find("fieldset.amendmentDeadline .date input").val() as string,
            amendScreening: parseInt(this.getRadioValue('amendScreening', 1), 10),
            hasComments: parseInt(this.getRadioValue('hasComments', 1), 10),
            applicationType: parseInt(this.getRadioValue('applicationType', 1), 10),
            speechQuotas: parseInt(this.getRadioValue('speechQuotas', 1), 10),
            speechLogin: parseInt(this.getRadioValue('speechLogin', 1), 10),
            openNow: parseInt(this.getRadioValue('openNow', 0), 10),
            title: $("#siteTitle").val() as string,
            organization: $("#siteOrganization").val() as string,
            subdomain: $("#siteSubdomain").val() as string,
            contact: $("#siteContact").val() as string
        };
    }

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

        // Workaround for Safari - it sometimes reloaded the page when clicking the "next" button
        window.setTimeout(() => {
            if ($panel.find("input:checked").length > 0) {
                $panel.find("input:checked").trigger("focus");
            } else if ($panel.find("button[type=submit]").length > 0) {
                $panel.find("button[type=submit]").trigger("focus");
            }
        }, 100);

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
    }

    private hasMotionlikeType (data: WizardState) {
        return data.functionality.indexOf(FUNCTIONALITY_MOTIONS) !== -1 || data.functionality.indexOf(FUNCTIONALITY_MANIFESTO) !== -1
             || data.functionality.indexOf(FUNCTIONALITY_STATUTE_AMENDMENTS) !== -1;
    }

    public panelConditions = {
        panelLanguage: () => this.firstPanel === '#panelLanguage',
        panelFunctionality: () => true,
        panelSingleMotion: (data: WizardState) => this.hasMotionlikeType(data),
        panelMotionWho: (data: WizardState) => this.hasMotionlikeType(data) && data.singleMotion === 0,
        panelMotionDeadline: (data: WizardState) => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelMotionScreening: (data: WizardState) => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelNeedsSupporters: (data: WizardState) => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelHasAmendments: (data: WizardState) => this.hasMotionlikeType(data),
        panelAmendSinglePara: (data: WizardState) => this.hasMotionlikeType(data) && data.hasAmendments === 1,
        panelAmendWho: (data: WizardState) => this.hasMotionlikeType(data) && data.hasAmendments === 1,
        panelAmendDeadline: (data: WizardState) => this.hasMotionlikeType(data) && data.hasAmendments === 1 && data.amendmentInitiatedBy !== 1, // MOTION_INITIATED_ADMINS,
        panelAmendScreening: (data: WizardState) => this.hasMotionlikeType(data) && data.hasAmendments === 1 && data.amendmentInitiatedBy !== 1,
        panelComments: (data: WizardState) => this.hasMotionlikeType(data),
        panelApplicationType: (data: WizardState) => data.functionality.indexOf(FUNCTIONALITY_APPLICATIONS) !== -1,
        panelSpeechLogin: (data: WizardState) => data.functionality.indexOf(FUNCTIONALITY_SPEECH_LISTS) !== -1,
        panelSpeechQuotas: (data: WizardState) => data.functionality.indexOf(FUNCTIONALITY_SPEECH_LISTS) !== -1,
        panelOpenNow: () => true,
        panelSiteData: () => true,
    };

    getNextPanel(): string {
        this.data = this.getWizardState();
        const currPanel = this.$activePanel.attr("id"),
            allPanelIds = Object.keys(this.panelConditions);

        let foundCurr = false;
        for (let i = 0; i < allPanelIds.length; i++) {
            if (allPanelIds[i] === currPanel) {
                // We ignore all steps previous to the current one
                foundCurr = true;
            } else if (foundCurr) {
                // Once we found the current one, we take the first step where the condition is met
                if (this.panelConditions[allPanelIds[i]](this.data)) {
                    return '#' + allPanelIds[i];
                }
            }
        }
        console.error("Could not find the next panel for " + currPanel + ", data: ", this.data);
    }

    subdomainChange(ev) {
        let $this = $(ev.currentTarget),
            subdomain = $this.val() as string,
            $group = $this.parents(".subdomainRow").first(),
            requesturl = $this.data("query-url").replace(/SUBDOMAIN/, subdomain),
            $err = $group.find(".subdomainError");

        if (subdomain === "") {
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

        $form.find("input").on("change", () => {
            this.data = this.getWizardState();
        });
        $form.find(".radio-label input").on("change", function () {
            let $fieldset = $(this).parents("fieldset").first();
            $fieldset.find(".radio-label").removeClass("active");
            let $active = $fieldset.find(".radio-label input:checked");
            $active.parents(".radio-label").first().addClass("active");
        }).trigger("change");
        $form.find(".checkbox-label input").on("change", function () {
            let $this = $(this);
            if ($this.prop("checked")) {
                $this.parents(".checkbox-label").first().addClass("active");
            } else {
                $this.parents(".checkbox-label").first().removeClass("active");
            }
        }).trigger("change");

        $form.find("fieldset.functionality input").on("change", function () {
            let wording = $form.find("fieldset.functionality input:checked").data("wording-name");
            $form.removeClass("wording_motion").removeClass("wording_manifesto").addClass("wording_" + wording);
        }).trigger("change");

        $form.find(".input-group.date").each(function () {
            let $this = $(this);
            $this.datetimepicker({
                locale: $this.find("input").data('locale')
            });
        });
        $form.find(".date.motionsDeadline").on("dp.change", function () {
            $("input.motionsDeadlineExists").prop("checked", true).trigger("change");
        });
        $form.find(".date.amendmentDeadline").on("dp.change", function () {
            $("input.amendDeadlineExists").prop("checked", true).trigger("change");
        });
        $form.find("input.minSupporters").on("change", () => {
            $("input.needsSupporters").prop("checked", true).trigger("change");
        });
        $form.find("#siteSubdomain").on("keyup change", this.subdomainChange.bind(this));
        $form.find("#siteTitle").on("keyup change", function () {
            if (($(this).val() as string).length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });
        $form.find("#siteOrganization").on("keyup change", function () {
            if (($(this).val() as string).length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });

        $form.find("#panelSiteData input").on("keypress", function (ev) {
            let original: any = ev.originalEvent;
            if (original.charCode === 13 || original.keyCode === 13) {
                ev.preventDefault();
            }
        });

        $form.find("#panelLanguage input").on("change", function() {
            const val = $form.find("#panelLanguage input:checked").val() as string;
            const url = $form.find("#panelLanguage").data("url").replace(/LNG/, val);
            window.location.href = url;
        });

        let obj = this;

        // The enter key should not submit the form, but lead to the next panel
        $form.on("keypress", (ev) => {
            if (ev.key === "Enter") {
                if (this.$activePanel.find(".btn-next").attr("type") !== "submit") {
                    ev.preventDefault();
                    ev.stopPropagation();
                    this.showPanel($(obj.getNextPanel()));
                }
            }
        });

        $form.find(".navigation .btn-next").on("click", (ev) => {
            if ($(ev.currentTarget).attr("type") === "submit") {
                return;
            }
            ev.preventDefault();
            obj.showPanel($(obj.getNextPanel()));
        });
        $form.find(".navigation .btn-prev").on("click", (ev) => {
            ev.preventDefault();
            if (window.location.hash != "") {
                window.history.back();
            }
        });

        $(window).on("hashchange", (ev) => {
            ev.preventDefault();
            let hash;
            if (window.location.hash === '' || parseInt(window.location.hash.substring(1)) === 0) {
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

