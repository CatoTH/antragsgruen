// @ts-check

import translations from "/js/vue/Translate.vue.js";

export class ConsultationSettings {
    /** @type { HTMLElement } */
    element;

    constructor(element) {
        this.element = element;
        this.initUrlPath();
        this.initTags();
        this.initAdminMayEdit();
        this.initSingleMotionMode();
        this.initConPwd();
        this.initManagedAccounts();

        $('[data-toggle="tooltip"]').tooltip();
    }

    initUrlPath() {
        $('.urlPathHolder .shower a').on("click", (ev) => {
            ev.preventDefault();
            $('.urlPathHolder .shower').addClass('hidden');
            $('.urlPathHolder .holder').removeClass('hidden');
        });
    }

    initSingleMotionMode() {
        $("#singleMotionMode").on("change", function () {
            if ($(this).prop("checked")) {
                $("#forceMotionRow").removeClass("hidden");
            } else {
                $("#forceMotionRow").addClass("hidden");
            }
        }).trigger("change");
    }

    initAdminMayEdit() {
        let $adminsMayEdit = $("#adminsMayEdit"),
            $iniatorsMayEdit = $("#iniatorsMayEdit").parents("label").first().parent();
        $adminsMayEdit.on("change", function () {
            if ($(this).prop("checked")) {
                $iniatorsMayEdit.removeClass("hidden");
            } else {
                let confirmMessage = translations.getTranslation("admin", "admin_may_edit_confirm");
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

    initTags() {
        const $form = $(this.element).find('#tagsEditForm');
        const $tagRowTemplate = $form.find(".newTagRowTemplate").remove();

        let activeTagType = 0;

        $form.find('.tagTypeSelector input').on('change', () => {
            const $selected = $form.find('.tagTypeSelector input:checked');
            activeTagType = $selected.val();

            $form.find('.editList').addClass('hidden');
            $form.find('.editList' + activeTagType).removeClass('hidden');
        }).trigger('change');

        document.querySelectorAll('#tagsEditForm .editList').forEach(tagList => {
            Sortable.create(tagList, {
                handle: '.drag-handle',
                animation: 150
            });
        });

        $form.find('.adderRow button').on('click', () => {
            const $newRow = $tagRowTemplate.clone();
            $newRow.find('.tagTypeInput').val(activeTagType);
            $form.find('.editList' + activeTagType).append($newRow);
            window.setTimeout(() => {
                $newRow.find("input").trigger('focus');
            }, 100);
        });

        $form.on('click', '.editList .remover', function (ev) {
            let $li = $(this).parents("li").first();
            ev.preventDefault();

            if ($li.data('has-imotions')) {
                bootbox.confirm($form.data('delete-warnings'), function (result) {
                    if (result) {
                        $li.remove();
                    }
                });
            } else {
                $li.remove();
            }
        });
    }

    initConPwd() {
        const widget = this.element.querySelector('.conpw');
        if (!widget) {
            return;
        }
        const checkbox = widget.querySelector('.setter input[type=checkbox]');
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

    initManagedAccounts() {
        const checkbox = this.element.querySelector('.managedUserAccounts input[type=checkbox]');
        const onCheckboxChange = () => {
            if (checkbox.checked) {
                this.element.querySelector('.allowRequestingAccess').classList.remove("hidden");
            } else {
                this.element.querySelector('.allowRequestingAccess').classList.add("hidden");
            }
        };
        checkbox.addEventListener('change', onCheckboxChange);
        onCheckboxChange();
    }
}
