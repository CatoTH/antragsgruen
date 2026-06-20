

// Sync with SiteCreateForm.php
const FUNCTIONALITY_MOTIONS = 1;
const FUNCTIONALITY_MANIFESTO = 2;
const FUNCTIONALITY_APPLICATIONS = 3;
const FUNCTIONALITY_AGENDA = 4;
const FUNCTIONALITY_SPEECH_LISTS = 5;
const FUNCTIONALITY_STATUTE_AMENDMENTS = 6;

/**
 * @typedef {Object} WizardState
 * @property {string}   language
 * @property {number[]} functionality
 * @property {number}   singleMotion
 * @property {number}   motionsInitiatedBy
 * @property {number}   motionsDeadlineExists
 * @property {string}   motionsDeadline
 * @property {number}   motionScreening
 * @property {number}   needsSupporters
 * @property {number}   minSupporters
 * @property {number}   hasAmendments
 * @property {number}   amendSinglePara
 * @property {number}   amendmentInitiatedBy
 * @property {number}   amendmentDeadlineExists
 * @property {string}   amendmentDeadline
 * @property {number}   amendScreening
 * @property {number}   hasComments
 * @property {number}   applicationType
 * @property {number}   speechQuotas
 * @property {number}   speechLogin
 * @property {number}   openNow
 * @property {string}   title
 * @property {string}   organization
 * @property {string}   subdomain
 * @property {string}   contact
 */

export class SiteCreateWizard {
    /** @type {JQuery} */      $root;
    /** @type {string} */      firstPanel;
    /** @type {string} */      mode;
    /** @type {WizardState} */ data;
    /** @type {JQuery} */      $activePanel;

    /** @type {Object<string, function(WizardState=): boolean>} */
    panelConditions = {
        panelLanguage:      ()           => this.firstPanel === '#panelLanguage',
        panelFunctionality: ()           => true,
        panelSingleMotion:  (data)       => this.hasMotionlikeType(data),
        panelMotionWho:     (data)       => this.hasMotionlikeType(data) && data.singleMotion === 0,
        panelMotionDeadline:(data)       => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelMotionScreening:(data)      => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelNeedsSupporters:(data)      => this.hasMotionlikeType(data) && data.singleMotion === 0 && data.motionsInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelHasAmendments: (data)       => this.hasMotionlikeType(data),
        panelAmendSinglePara:(data)      => this.hasMotionlikeType(data) && data.hasAmendments === 1,
        panelAmendWho:      (data)       => this.hasMotionlikeType(data) && data.hasAmendments === 1,
        panelAmendDeadline: (data)       => this.hasMotionlikeType(data) && data.hasAmendments === 1 && data.amendmentInitiatedBy !== 1, // MOTION_INITIATED_ADMINS
        panelAmendScreening:(data)       => this.hasMotionlikeType(data) && data.hasAmendments === 1 && data.amendmentInitiatedBy !== 1,
        panelComments:      (data)       => this.hasMotionlikeType(data),
        panelApplicationType:(data)      => data.functionality.indexOf(FUNCTIONALITY_APPLICATIONS) !== -1,
        panelSpeechLogin:   (data)       => data.functionality.indexOf(FUNCTIONALITY_SPEECH_LISTS) !== -1,
        panelSpeechQuotas:  (data)       => data.functionality.indexOf(FUNCTIONALITY_SPEECH_LISTS) !== -1,
        panelOpenNow:       ()           => true,
        panelSiteData:      ()           => true,
    };

    /**
     * @param {HTMLElement} root
     */
    constructor(root) {
        this.$root = $(root);
        this.firstPanel = $("#SiteCreateWizard").data("init-step");
        this.mode = $("#SiteCreateWizard").data("mode");
        this.initEvents();
    }

    /**
     * @param {string} fieldsetClass
     * @param {any} defaultVal
     * @returns {any}
     */
    getRadioValue(fieldsetClass, defaultVal) {
        const $input = this.$root.find("fieldset." + fieldsetClass).find("input:checked");
        return $input.length > 0 ? $input.val() : defaultVal;
    }

    /**
     * @param {string} fieldsetClass
     * @param {any} defaultVals
     * @returns {any}
     */
    getCheckboxValues(fieldsetClass, defaultVals) {
        const inputs = this.$root.find("fieldset." + fieldsetClass).find("input:checked").toArray();
        if (inputs.length > 0) {
            return inputs.map((element) => parseInt(element.getAttribute('value'), 10));
        }
        return defaultVals;
    }

    /**
     * @returns {WizardState}
     */
    getWizardState() {
        /**
         * @param {string} val
         * @returns {number|null}
         */
        const parseNullableNumber = (val) => {
            if (val === '' || val === null) {
                return null;
            }
            return parseInt(val, 10);
        };

        return {
            language:               this.getRadioValue('language', null),
            functionality:          this.getCheckboxValues('functionality', []),
            singleMotion:           parseInt(this.getRadioValue('singleMotion', 0), 10),
            motionsInitiatedBy:     parseInt(this.getRadioValue('motionWho', 1), 10),
            motionsDeadlineExists:  parseInt(this.getRadioValue('motionDeadline', 0), 10),
            motionsDeadline:        /** @type {string} */ (this.$root.find("fieldset.motionDeadline .date input").val()),
            motionScreening:        parseInt(this.getRadioValue('motionScreening', 1), 10),
            needsSupporters:        parseInt(this.getRadioValue('needsSupporters', 0), 10),
            minSupporters:          parseNullableNumber(/** @type {string} */ (this.$root.find("input.minSupporters").val())),
            hasAmendments:          parseInt(this.getRadioValue('hasAmendments', 1), 10),
            amendSinglePara:        parseInt(this.getRadioValue('amendSinglePara', 0), 10),
            amendmentInitiatedBy:   parseInt(this.getRadioValue('amendmentWho', 1), 10),
            amendmentDeadlineExists:parseInt(this.getRadioValue('amendmentDeadline', 0), 10),
            amendmentDeadline:      /** @type {string} */ (this.$root.find("fieldset.amendmentDeadline .date input").val()),
            amendScreening:         parseInt(this.getRadioValue('amendScreening', 1), 10),
            hasComments:            parseInt(this.getRadioValue('hasComments', 1), 10),
            applicationType:        parseInt(this.getRadioValue('applicationType', 1), 10),
            speechQuotas:           parseInt(this.getRadioValue('speechQuotas', 1), 10),
            speechLogin:            parseInt(this.getRadioValue('speechLogin', 1), 10),
            openNow:                parseInt(this.getRadioValue('openNow', 0), 10),
            title:                  /** @type {string} */ ($("#siteTitle").val()),
            organization:           /** @type {string} */ ($("#siteOrganization").val()),
            subdomain:              /** @type {string} */ ($("#siteSubdomain").val()),
            contact:                /** @type {string} */ ($("#siteContact").val()),
        };
    }

    /**
     * @param {JQuery} $panel
     */
    showPanel($panel) {
        this.data = this.getWizardState();

        const step = $panel.data("tab");
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
            let isCorrect = (window.location.hash === "#" + $panel.attr("id"));
            if ((window.location.hash === "" || window.location.hash === "#") && "#" + $panel.attr("id") === this.firstPanel) {
                isCorrect = true;
            }
            if (!isCorrect) {
                window.location.hash = "#" + $panel.attr("id").substring(5);
            }
        } catch (e) {
            console.log(e);
        }
    }

    /**
     * @param {WizardState} data
     * @returns {boolean}
     */
    hasMotionlikeType(data) {
        return data.functionality.indexOf(FUNCTIONALITY_MOTIONS) !== -1
            || data.functionality.indexOf(FUNCTIONALITY_MANIFESTO) !== -1
            || data.functionality.indexOf(FUNCTIONALITY_STATUTE_AMENDMENTS) !== -1;
    }

    /**
     * @returns {string}
     */
    getNextPanel() {
        this.data = this.getWizardState();
        const currPanel = this.$activePanel.attr("id"),
            allPanelIds = Object.keys(this.panelConditions);

        let foundCurr = false;
        for (let i = 0; i < allPanelIds.length; i++) {
            if (allPanelIds[i] === currPanel) {
                foundCurr = true;
            } else if (foundCurr) {
                if (this.panelConditions[allPanelIds[i]](this.data)) {
                    return '#' + allPanelIds[i];
                }
            }
        }
        console.error("Could not find the next panel for " + currPanel + ", data: ", this.data);
    }

    /**
     * @param {JQuery.TriggeredEvent} ev
     */
    subdomainChange(ev) {
        const $this = $(ev.currentTarget),
            subdomain = /** @type {string} */ ($this.val()),
            $group = $this.parents(".subdomainRow").first(),
            requesturl = $this.data("query-url").replace(/SUBDOMAIN/, subdomain),
            $err = $group.find(".subdomainError");

        if (subdomain === "") {
            $err.addClass("hidden");
            $group.removeClass("has-error").removeClass("has-success");
            return;
        }
        if (!subdomain.match(/^[A-Z0-9äöü](?:[A-Z0-9äöü_-]{0,61}[A-Z0-9äöü])?$/i)) {
            $group.removeClass("has-success").addClass("has-error");
            this.$root.find("button[type=submit]").prop("disabled", true);
            return;
        }
        $.get(requesturl, (ret) => {
            if (ret['available']) {
                $err.addClass("hidden");
                $group.removeClass("has-error");
                this.$root.find("button[type=submit]").prop("disabled", false);
                if (ret['subdomain'] === $this.val()) {
                    $group.addClass("has-success");
                }
            } else {
                $err.removeClass("hidden");
                $err.html($err.data("template").replace(/%SUBDOMAIN%/, ret['subdomain']));
                $group.removeClass("has-success");
                if (ret['subdomain'] === $this.val()) {
                    this.$root.find("button[type=submit]").prop("disabled", true);
                    $group.addClass("has-error");
                }
            }
        });
    }

    initEvents() {
        const $form = this.$root;

        this.$activePanel = null;
        this.data = this.getWizardState();

        $form.find("input").on("change", () => {
            this.data = this.getWizardState();
        });
        $form.find(".radio-label input").on("change", function () {
            const $fieldset = $(this).parents("fieldset").first();
            $fieldset.find(".radio-label").removeClass("active");
            const $active = $fieldset.find(".radio-label input:checked");
            $active.parents(".radio-label").first().addClass("active");
        }).trigger("change");
        $form.find(".checkbox-label input").on("change", function () {
            const $this = $(this);
            if ($this.prop("checked")) {
                $this.parents(".checkbox-label").first().addClass("active");
            } else {
                $this.parents(".checkbox-label").first().removeClass("active");
            }
        }).trigger("change");

        $form.find("fieldset.functionality input").on("change", function () {
            const wording = $form.find("fieldset.functionality input:checked").data("wording-name");
            $form.removeClass("wording_motion").removeClass("wording_manifesto").addClass("wording_" + wording);
        }).trigger("change");

        $form.find(".input-group.date").each(function () {
            const $this = $(this);
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
            const hasLength = (/** @type {string} */ ($(this).val())).length >= 5;
            $(this).parents(".form-group").first().toggleClass("has-success", hasLength);
        });
        $form.find("#siteOrganization").on("keyup change", function () {
            const hasLength = (/** @type {string} */ ($(this).val())).length >= 5;
            $(this).parents(".form-group").first().toggleClass("has-success", hasLength);
        });

        $form.find("#panelSiteData input").on("keypress", function (ev) {
            const original = /** @type {any} */ (ev.originalEvent);
            if (original.charCode === 13 || original.keyCode === 13) {
                ev.preventDefault();
            }
        });

        $form.find("#panelLanguage input").on("change", function () {
            const val = /** @type {string} */ ($form.find("#panelLanguage input:checked").val());
            const url = new URL($form.find("#panelLanguage").data("url").replace(/LNG/, val), window.location.origin);
            const path = url.pathname + url.search;

            if (/^\/[a-zA-Z0-9\-_/?=&.,]*$/.test(path)) {
                window.location.href = path;
            } else {
                console.error("Rejected unsafe redirect path:", path);
            }
        });

        // The enter key should not submit the form, but lead to the next panel
        $form.on("keypress", (ev) => {
            if (ev.key === "Enter") {
                if (this.$activePanel.find(".btn-next").attr("type") !== "submit") {
                    ev.preventDefault();
                    ev.stopPropagation();
                    this.showPanel($(this.getNextPanel()));
                }
            }
        });

        $form.find(".navigation .btn-next").on("click", (ev) => {
            if ($(ev.currentTarget).attr("type") === "submit") {
                return;
            }
            ev.preventDefault();
            this.showPanel($(this.getNextPanel()));
        });
        $form.find(".navigation .btn-prev").on("click", (ev) => {
            ev.preventDefault();
            if (window.location.hash !== "") {
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
            const $panel = $(hash);
            if ($panel.length > 0) {
                this.showPanel($panel);
            }
        });

        $form.find(".step-pane").addClass("inactive");
        this.showPanel($(this.firstPanel));
    }
}
