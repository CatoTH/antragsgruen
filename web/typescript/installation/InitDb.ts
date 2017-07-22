export class InitDb {
    private dbTestUrl: string;

    constructor(private $form: JQuery) {
        let $testDBcaller = $form.find(".testDBcaller");
        this.dbTestUrl = $testDBcaller.data('url');

        $('#sqlPassword').on('keyup', function () {
            $('#sqlPasswordNone').prop('checked', false);
        });
        $('#sqlPasswordNone').on('change', function () {
            if ($(this).prop('checked')) {
                $('#sqlPassword').val('').attr('placeholder', '');
            }
        });

        $testDBcaller.click(this.testDb.bind(this));
        if ($('#sqlHost').val() != '' || $('#sqlPassword').val() != '') {
            $testDBcaller.click();
        }

        $("#language").on('changed.fu.selectlist', this.gotoLanguageVariant.bind(this));
    }

    private gotoLanguageVariant(ev, data) {
        let href = window.location.href.split('?')[0];
        href += '?language=' + data.value;
        window.location.href = href;
    }

    private testDb() {
        let $pending = $('.testDBRpending'),
            $success = $('.testDBsuccess'),
            $error = $('.testDBerror'),
            $createTables = $('.createTables'),
            csrf = $('input[name=_csrf]').val(),
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

        $.post(this.dbTestUrl, params, function (ret) {
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
        }).fail(function(err) {
            alert("An internal error occurred: " + err.status + " / " + err.responseText);
        });
    }
}
