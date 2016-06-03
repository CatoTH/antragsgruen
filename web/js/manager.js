/*global browser: true, regexp: true */
/*global $, jQuery, alert, console */
/*jslint regexp: true*/

(function ($) {
    "use strict";

    var createInstance2 = function () {
        var firstPanel = "#panelPurpose",
            $form = $("form.siteCreate"),
            $activePanel = null,
            getRadioValue = function (fieldsetClass, defaultVal) {
                var $input = $("fieldset." + fieldsetClass).find("input:checked");
                if ($input.length > 0) {
                    return $input.val();
                } else {
                    return defaultVal;
                }
            },
            getWizardState = function () {
                return {
                    wording: getRadioValue('wording', 1),
                    singleMotion: getRadioValue('singleMotion', 0),
                    motionsInitiatedBy: getRadioValue('motionWho', 1),
                    motionsDeadlineExists: getRadioValue('motionDeadline', 0),
                    motionsDeadline: $form.find("fieldset.motionDeadline .date input").val(),
                    motionScreening: getRadioValue('motionScreening', 1),
                    needsSupporters: getRadioValue('needsSupporters', 0),
                    minSupporters: $form.find("input.minSupporters").val(),
                    hasAmendments: getRadioValue('hasAmendments', 1),
                    amendSinglePara: getRadioValue('amendSinglePara', 0),
                    amendmentInitiatedBy: getRadioValue('amendmentWho', 1),
                    amendmentDeadlineExists: getRadioValue('amendmentDeadline', 0),
                    amendmentDeadline: $form.find("fieldset.amendmentDeadline .date input").val(),
                    amendScreening: getRadioValue('amendScreening', 1),
                    hasComments: getRadioValue('hasComments', 1),
                    hasAgenda: getRadioValue('hasAgenda', 0),
                    openNow: getRadioValue('openNow', 0),
                    title: $("#siteTitle").val(),
                    organization: $("#siteOrganization").val(),
                    subdomain: $("#siteSubdomain").val(),
                    contact: $("#siteContact").val()
                };
            },
            showPanel = function ($panel) {
                data = getWizardState();
                console.log(data);

                var step = $panel.data("tab");
                $form.find(".wizard .steps li").removeClass("active");
                $form.find(".wizard .steps ." + step).addClass("active");

                if ($activePanel) {
                    $activePanel.removeClass("active").addClass("inactive");
                }
                $panel.addClass("active").removeClass("inactive");
                $activePanel = $panel;

                try {
                    var isCorrect = (window.location.hash == "#" + $panel.attr("id"));
                    if ((window.location.hash == "" || window.location.hash == "#") && "#" + $panel.attr("id") == firstPanel) {
                        isCorrect = true;
                    }
                    if (!isCorrect) {
                        console.log("change");
                        window.location.hash = "#" + $panel.attr("id");
                    }
                } catch (e) {
                    console.log(e);
                }
            },
            getNextPanel = function ($currPanel) {
                switch ($currPanel.attr("id")) {
                    case 'panelPurpose':
                        return $("#panelSingleMotion");
                    case 'panelSingleMotion':
                        return $("#panelMotionWho");
                    case 'panelMotionWho':
                        return $("#panelMotionDeadline");
                    case 'panelMotionDeadline':
                        return $("#panelMotionScreening");
                    case 'panelMotionScreening':
                        return $("#panelNeedsSupporters");
                    case 'panelNeedsSupporters':
                        return $("#panelHasAmendments");
                    case 'panelHasAmendments':
                        return $("#panelAmendSinglePara");
                    case 'panelAmendSinglePara':
                        return $("#panelAmendWho");
                    case 'panelAmendWho':
                        return $("#panelAmendDeadline");
                    case 'panelAmendDeadline':
                        return $("#panelAmendScreening");
                    case 'panelAmendScreening':
                        return $("#panelComments");
                    case 'panelComments':
                        return $("#panelAgenda");
                    case 'panelAgenda':
                        return $("#panelOpenNow");
                    case 'panelOpenNow':
                        return $("#panelSiteData")
                }
            },
            data = getWizardState;

        $form.find("input").change(function () {
            data = getWizardState();
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

        $form.find(".navigation .btn-next").click(function (ev) {
            if ($(this).attr("type") == "submit") {
                return;
            }
            ev.preventDefault();
            showPanel(getNextPanel($activePanel));
        });
        $form.find(".navigation .btn-prev").click(function (ev) {
            ev.preventDefault();
            if (window.location.hash != "") {
                window.history.back();
            }
        });
        $form.submit(function (ev) {

        });

        $(window).on("hashchange", function (ev) {
            ev.preventDefault();
            var hash = window.location.hash;
            if (hash.length == 0) {
                hash = firstPanel;
            }
            var $panel = $(hash);
            if ($panel.length > 0) {
                showPanel($panel);
            }
        });

        $form.find(".step-pane").addClass("inactive");
        showPanel($(firstPanel));
    };

    var createInstance = function () {
        var $steps = $('#SiteCreateWizard').find('li'),
            $step1 = $('#step1'),
            $step2 = $('#step2'),
            $step3 = $('#step3');
        $step2.addClass("hidden");
        $step3.addClass("hidden");
        $('#next-1').click(function (ev) {
            ev.preventDefault();
            $step1.addClass("hidden");
            $step2.removeClass("hidden");
            $steps.eq(0).removeClass('active');
            $steps.eq(1).addClass('active');
        });
        $('#next-2').click(function (ev) {
            ev.preventDefault();
            if ($('#siteTitle').val() == '') {
                bootbox.alert('Bitte gib den Namen der neuen Seite an.');
                return;
            }
            if ($('#subdomain').val() == '') {
                bootbox.alert('Es muss eine Subdomain ("Unter folgender Adresse soll es erreichbar sein") für die neue Seite angegeben werden.');
                return;
            }
            if ($('#subdomain').val().match(/[^a-zA-Z0-9_\-]/)) {
                bootbox.alert('Die Subdomain ("Unter folgender Adresse soll es erreichbar sein") darf nur Zahlen, Buchstaben, Unter- und Mittelstrich enthalten.');
                return;
            }
            $step2.addClass("hidden");
            $step3.removeClass("hidden");
            $steps.eq(1).removeClass('active');
            $steps.eq(2).addClass('active');
            window.scrollTo(0, 0);
        });
        $('#subdomain').on('blur', function () {
            var $this = $(this);
            if ($this.val().match(/[^a-zA-Z0-9_\-]/)) {
                bootbox.alert('Die Subdomain ("Unter folgender Adresse soll es erreichbar sein") darf nur Zahlen, Buchstaben, Unter- und Mittelstrich enthalten.');
            }
        });
        /*
         $step3.find('button[type=submit]').click(function (ev) {
         console.log(ev);
         });
         */
        $step1.find('.sitePreset input').change(function () {
            var $this = $(this);
            if (!$this.prop('checked')) {
                return;
            }
            var defaults = $this.parents('label').first().data("defaults");
            $step2.find(".hasComments").prop('checked', defaults['comments']);
            $step2.find(".hasAmendments").prop('checked', defaults['amendments']);
            $step2.find(".openNow").prop('checked', defaults['openNow']);
        }).change();
    };

    var siteConfig = function () {
        var rebuildVisibility = function () {
            var transport = $("[name=\"mailService[transport]\"]").val(),
                auth = $("[name=\"mailService[smtpAuthType]\"]").val();

            $('.emailOption').hide();
            if (transport == 'sendmail') {
                // Nothing to do
            } else if (transport == 'mandrill') {
                $('.emailOption.mandrillApiKey').show();
            } else if (transport == 'mailgun') {
                $('.emailOption.mailgunApiKey').show();
                $('.emailOption.mailgunDomain').show();
            } else if (transport == 'smtp') {
                $('.emailOption.smtpHost').show();
                $('.emailOption.smtpPort').show();
                $('.emailOption.smtpAuthType').show();
                if (auth != 'none') {
                    $('.emailOption.smtpUsername').show();
                    $('.emailOption.smtpPassword').show();
                }
            }
        };
        $("#smtpAuthType").on("changed.fu.selectlist", rebuildVisibility);
        $("#emailTransport").on("changed.fu.selectlist", rebuildVisibility).trigger("changed.fu.selectlist");
    };

    var antragsgruenInit = function () {
        $('#sqlPassword').on('keyup', function () {
            $('#sqlPasswordNone').prop('checked', false);
        });
        $('#sqlPasswordNone').on('change', function () {
            if ($(this).prop('checked')) {
                $('#sqlPassword').val('').attr('placeholder', '');
            }
        });
        $('.testDBcaller').click(function () {
            var $pending = $('.testDBRpending'),
                $success = $('.testDBsuccess'),
                $error = $('.testDBerror'),
                $createTables = $('.createTables'),
                csrf = $('input[name=_csrf]').val(),
                url = $(this).data('url'),
                params = {
                    'sqlType': $("input[name=sqlType]").val(),
                    'sqlHost': $("input[name=sqlHost]").val(),
                    'sqlUsername': $("input[name=sqlUsername]").val(),
                    'sqlPassword': $("input[name=sqlPassword]").val(),
                    'sqlDB': $("input[name=sqlDB]").val(),
                    '_csrf': csrf
                };
            if ($("input[name=sqlPasswordNone]").prop("checked")) {
                params['sqlPasswordNone'] = 1;
            }
            $pending.removeClass('hidden');
            $error.addClass('hidden');
            $success.addClass('hidden');

            $.post(url, params, function (ret) {
                if (ret['success']) {
                    $success.removeClass('hidden');
                    if (ret['alreadyCreated']) {
                        $createTables.addClass('alreadyCreated');
                    } else {
                        $createTables.removeClass('alreadyCreated');
                    }
                } else {
                    $error.removeClass('hidden');
                    $error.find('.result').text(ret['error']);
                    $createTables.removeClass('alreadyCreated');
                }
                $pending.addClass('hidden');
            });
        });
    };

    $.SiteManager = {
        "createInstance": createInstance,
        "createInstance2": createInstance2,
        "siteConfig": siteConfig,
        "antragsgruenInit": antragsgruenInit
    };

}(jQuery));
