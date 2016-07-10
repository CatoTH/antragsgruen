class SiteCreateWizard {
    getRadioValue(fieldsetClass, defaultVal) {
        var $input = this.$root.find("fieldset." + fieldsetClass).find("input:checked");
        if ($input.length > 0) {
            return $input.val();
        } else {
            return defaultVal;
        }
    };

    getWizardState() {
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

    showPanel($panel) {
        this.data = this.getWizardState();
        console.log(this.data);

        var step = $panel.data("tab");
        this.$root.find(".wizard .steps li").removeClass("active");
        this.$root.find(".wizard .steps ." + step).addClass("active");

        if (this.$activePanel) {
            this.$activePanel.removeClass("active").addClass("inactive");
        }
        $panel.addClass("active").removeClass("inactive");
        this.$activePanel = $panel;

        try {
            var isCorrect = (window.location.hash == "#" + $panel.attr("id"));
            if ((window.location.hash == "" || window.location.hash == "#") && "#" + $panel.attr("id") == this.firstPanel) {
                isCorrect = true;
            }
            if (!isCorrect) {
                console.log("change");
                window.location.hash = "#" + $panel.attr("id").substring(5);
            }
        } catch (e) {
            console.log(e);
        }
    };

    getNextPanel() {
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

    initEvents() {
        var $form = this.$root,
            $ = this.$;

        this.$activePanel = null;
        this.data = this.getWizardState();

        $form.find("input").change(() => {
            this.data = this.getWizardState();
        });
        $form.find(".radio-label input").change(function () {
            var $fieldset = $(this).parents("fieldset").first();
            $fieldset.find(".radio-label").removeClass("active");
            var $active = $fieldset.find(".radio-label input:checked");
            $active.parents(".radio-label").first().addClass("active");
        }).trigger("change");

        $form.find("fieldset.wording input").change(function () {
            var wording = $form.find("fieldset.wording input:checked").data("wording-name");
            $form.removeClass("wording_motion").removeClass("wording_manifesto").addClass("wording_" + wording);
        }).trigger("change");

        $form.find(".input-group.date").each(function () {
            var $this = $(this);
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
        $form.find("#siteSubdomain").on("keyup change", function () {
            var $this = $(this),
                subdomain = $this.val(),
                $group = $this.parents(".subdomainRow").first(),
                requesturl = $this.data("query-url").replace(/SUBDOMAIN/, subdomain),
                $err = $group.find(".subdomainError");

            if (subdomain == "") {
                $err.addClass("hidden");
                $group.removeClass("has-error").removeClass("has-success");
                return;
            }
            $.get(requesturl, function (ret) {
                if (ret['available']) {
                    $err.addClass("hidden");
                    $group.removeClass("has-error");
                    $form.find("button[type=submit]").prop("disabled", false);
                    if (ret['subdomain'] == $this.val()) {
                        $group.addClass("has-success");
                    }
                } else {
                    $err.removeClass("hidden");
                    $err.html($err.data("template").replace(/%SUBDOMAIN%/, ret['subdomain']));
                    $group.removeClass("has-success");
                    if (ret['subdomain'] == $this.val()) {
                        $form.find("button[type=submit]").prop("disabled", true);
                        $group.addClass("has-error");
                    }
                }
            });
        });
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

        var obj = this;
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
        $form.submit(function (ev) {

        });

        $(window).on("hashchange", (ev) => {
            ev.preventDefault();
            var hash;
            if (window.location.hash.substring(1) == 0) {
                hash = this.firstPanel;
            } else {
                hash = "#panel" + window.location.hash.substring(1);
            }
            var $panel = $(hash);
            if ($panel.length > 0) {
                this.showPanel($panel);
            }
        });

        $form.find(".step-pane").addClass("inactive");
        this.showPanel($(this.firstPanel));
    }

    constructor($, $root) {
        this.$ = $;
        this.firstPanel = "#panelPurpose";
        this.$root = $root;
        this.mode = $("#SiteCreateWizard").data("mode");
        this.initEvents();
    }
}

