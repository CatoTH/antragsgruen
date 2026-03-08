// @ts-check

/**
 * @typedef {Object} DbTestResult
 *
 * @property {boolean} success
 * @property {boolean} [alreadyCreated]
 * @property {string} [error]
 */

export class InitDb {

    /** @type {JQuery} */
    $form

    /** @type {string} */
    dbTestUrl

    /** @type {string} */
    dbTestUrlNotSoPretty

    /**
     * @param {JQuery} $form
     */
    constructor($form) {
        this.$form = $form

        const $testDBcaller = $form.find(".testDBcaller")

        this.dbTestUrl = $testDBcaller.data("url")
        this.dbTestUrlNotSoPretty = $testDBcaller.data("url-not-so-pretty")

        $("#sqlPassword").on("keyup", () => {
            $("#sqlPasswordNone").prop("checked", false)
        })

        $("#sqlPasswordNone").on("change", (e) => {
            const $target = $(e.currentTarget)

            if ($target.prop("checked")) {
                $("#sqlPassword").val("").attr("placeholder", "")
            }
        })

        $testDBcaller.on("click", this.testDb)

        if ($("#sqlHost").val() !== "" || $("#sqlPassword").val() !== "") {
            $testDBcaller.trigger("click")
        }

        $("#language").on("change", this.gotoLanguageVariant)
    }

    gotoLanguageVariant = () => {
        let href = window.location.href.split("?")[0]
        href += "?language=" + $("#language").val()
        window.location.href = href
    }

    /**
     * @param {DbTestResult} ret
     */
    testDbResult = (ret) => {
        const $pending = $(".testDBRpending")
        const $success = $(".testDBsuccess")
        const $error = $(".testDBerror")
        const $createTables = $(".createTables")

        if (ret.success) {
            $success.removeClass("hidden")

            if (ret.alreadyCreated) {
                $createTables.addClass("alreadyCreated")
            } else {
                $createTables.removeClass("alreadyCreated")
            }

        } else {
            $error.removeClass("hidden")
            $error.find(".result").text(ret.error)
            $createTables.removeClass("alreadyCreated")
        }

        $pending.addClass("hidden")
    }

    testDb = () => {

        const $pending = $(".testDBRpending")
        const $success = $(".testDBsuccess")
        const $error = $(".testDBerror")

        const csrf = $('input[name=_csrf]').val()

        const params = {
            sqlType: $("input[name=sqlType]").val(),
            sqlHost: $("input[name=sqlHost]").val(),
            sqlUsername: $("input[name=sqlUsername]").val(),
            sqlPassword: $("input[name=sqlPassword]").val(),
            sqlDB: $("input[name=sqlDB]").val(),
            _csrf: csrf
        }

        if ($("input[name=sqlPasswordNone]").prop("checked")) {
            params["sqlPasswordNone"] = 1
        }

        $pending.removeClass("hidden")
        $error.addClass("hidden")
        $success.addClass("hidden")

        $.post(this.dbTestUrl, params, this.testDbResult)
            .fail((err) => {

                if (err.status === 404) {

                    params["disablePrettyUrl"] = "1"

                    $.post(this.dbTestUrlNotSoPretty, params, (ret) => {
                        this.testDbResult(ret)
                        $('input[name=prettyUrls]').val("0")
                    })
                        .fail((err) => {
                            alert("An internal error occurred: " + err.status + " / " + err.responseText)
                        })

                } else {
                    alert("An internal error occurred: " + err.status + " / " + err.responseText)
                }

            })
    }

}
