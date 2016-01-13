/*global browser: true, regexp: true */
/*global $, jQuery, alert, console */
/*jslint regexp: true*/

(function ($) {
    "use strict";
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
        "siteConfig": siteConfig,
        "antragsgruenInit": antragsgruenInit
    };

}(jQuery));
