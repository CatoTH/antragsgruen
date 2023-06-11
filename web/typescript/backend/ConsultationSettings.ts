export class ConsultationSettings {
    private element: HTMLFormElement;

    constructor(private $form: JQuery) {
        this.element = $form[0] as HTMLFormElement;
        this.initUrlPath();
        this.initTags();
        this.initOrganisations();
        this.initAdminMayEdit();
        this.initSingleMotionMode();
        this.initConPwd();

        $('[data-toggle="tooltip"]').tooltip();
    }

    private initUrlPath() {
        $('.urlPathHolder .shower a').on("click", (ev) => {
            ev.preventDefault();
            $('.urlPathHolder .shower').addClass('hidden');
            $('.urlPathHolder .holder').removeClass('hidden');
        });
    }

    private initSingleMotionMode() {
        $("#singleMotionMode").on("change", function () {
            if ($(this).prop("checked")) {
                $("#forceMotionRow").removeClass("hidden");
            } else {
                $("#forceMotionRow").addClass("hidden");
            }
        }).trigger("change");
    }

    private initAdminMayEdit() {
        let $adminsMayEdit = $("#adminsMayEdit"),
            $iniatorsMayEdit = $("#iniatorsMayEdit").parents("label").first().parent();
        $adminsMayEdit.on("change", function () {
            if ($(this).prop("checked")) {
                $iniatorsMayEdit.removeClass("hidden");
            } else {
                let confirmMessage = __t("admin", "adminMayEditConfirm");
                bootbox.confirm(confirmMessage, function (result) {
                    if (result) {
                        $iniatorsMayEdit.addClass("hidden");
                        $iniatorsMayEdit.find("input").prop("checked", false);
                    } else {
                        $adminsMayEdit.prop("checked", true);
                    }
                });
            }
        });
        if (!$adminsMayEdit.prop("checked")) $iniatorsMayEdit.addClass("hidden");
    }

    private initTags() {
        const $tagList: any = this.$form.find("#tagsList select");
        $tagList.selectize({
            create: true,
            plugins: ["remove_button"],
            render: {
                option_create: (data, escape) => {
                    return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                }
            }
        });
    }

    private initOrganisations() {
        const $tagList: any = this.$form.find("#organisationList select");
        $tagList.selectize({
            create: true,
            plugins: ["remove_button"],
            render: {
                option_create: (data, escape) => {
                    return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                }
            }
        })
    }

    private initConPwd() {
        const widget = this.element.querySelector('.conpw');
        if (!widget) {
            return;
        }
        const checkbox = widget.querySelector('.setter input[type=checkbox]') as HTMLInputElement;
        const onCheckboxChange = () => {
            if (checkbox.checked) {
                widget.classList.add("checked");
            } else {
                widget.classList.remove("checked");
            }
        };
        checkbox.addEventListener('change', onCheckboxChange);
        onCheckboxChange();

        widget.querySelector('.setNewPassword').addEventListener('click', ev => {
            ev.preventDefault();
            ev.stopPropagation();
            widget.classList.add('changePwd');
        });
    }
}
