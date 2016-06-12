/*global browser: true, regexp: true */
/*global $, jQuery, alert, console */
/*jslint regexp: true*/

(function ($) {
    "use strict";

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

    var antragsgruenInitDb = function () {
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
        "siteConfig": siteConfig,
        "antragsgruenInitDb": antragsgruenInitDb
    };

}(jQuery));
