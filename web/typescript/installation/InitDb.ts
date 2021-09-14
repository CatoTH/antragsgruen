export class InitDb {
    private dbTestUrl: string;
    private dbTestUrlNotSoPretty: string;

    constructor(private $form: JQuery) {
        let $testDBcaller = $form.find(".testDBcaller");
        this.dbTestUrl = $testDBcaller.data('url');
        this.dbTestUrlNotSoPretty = $testDBcaller.data('url-not-so-pretty');

        $('#sqlPassword').on('keyup', function () {
            $('#sqlPasswordNone').prop('checked', false);
        });
        $('#sqlPasswordNone').on('change', function () {
            if ($(this).prop('checked')) {
                $('#sqlPassword').val('').attr('placeholder', '');
            }
        });

        $testDBcaller.on("click", this.testDb.bind(this));
        if ($('#sqlHost').val() != '' || $('#sqlPassword').val() != '') {
            $testDBcaller.trigger("click");
        }

        $("#language").on('change', this.gotoLanguageVariant.bind(this));
    }

    private gotoLanguageVariant() {
        let href = window.location.href.split('?')[0];
        href += '?language=' + $("#language").val();
        window.location.href = href;
    }

    private testDbResult(ret) {
        let $pending = $('.testDBRpending'),
            $success = $('.testDBsuccess'),
            $error = $('.testDBerror'),
            $createTables = $('.createTables');
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
    }

    private testDb() {
        let $pending = $('.testDBRpending'),
            $success = $('.testDBsuccess'),
            $error = $('.testDBerror'),

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

        $.post(this.dbTestUrl, params, this.testDbResult.bind(this)).fail((err) => {
            if (err.status === 404) {
                params['disablePrettyUrl'] = '1';
                $.post(this.dbTestUrlNotSoPretty, params, (ret) => {
                    this.testDbResult(ret);
                    $('input[name=prettyUrls]').val('0');
                }).fail((err) => {
                    alert("An internal error occurred: " + err.status + " / " + err.responseText);
                });
            } else {
                alert("An internal error occurred: " + err.status + " / " + err.responseText);
            }
        });
    }
}
