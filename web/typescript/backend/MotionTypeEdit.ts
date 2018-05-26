class MotionTypeEdit {
    constructor() {
        let $supportType = $('#typeSupportType');
        $supportType.on('changed.fu.selectlist', () => {
            let selected = $supportType.find('input').val();
            let hasSupporters = $supportType.find("li[data-value=\"" + selected + "\"]").data("has-supporters");

            if (hasSupporters) {
                $('#typeMinSupportersRow').removeClass("hidden");
                $('#typeAllowMoreSupporters').removeClass("hidden");
            } else {
                $('#typeMinSupportersRow').addClass("hidden");
                $('#typeAllowMoreSupporters').addClass("hidden");
            }
        }).trigger('changed.fu.selectlist');

        $('.deleteTypeOpener a').on('click', (ev) => {
            ev.preventDefault();
            $('.deleteTypeForm').removeClass('hidden');
            $('.deleteTypeOpener').addClass('hidden');
        });

        $('[data-toggle="tooltip"]').tooltip();

        this.initSectionList();
        this.initDeadlines();
    }

    private initDeadlines() {
        $('#deadlineFormTypeComplex input').change((ev) => {
            if ($(ev.currentTarget).prop('checked')) {
                $('.deadlineTypeSimple').addClass('hidden');
                $('.deadlineTypeComplex').removeClass('hidden');
            } else {
                $('.deadlineTypeSimple').removeClass('hidden');
                $('.deadlineTypeComplex').addClass('hidden');
            }
        }).trigger('change');

        $('.datetimepicker').each((i, el) => {
            $(el).datetimepicker({
                locale: $(el).find("input").data('locale')
            });
        });

        $('.deadlineHolder').each((i, el) => {
            const $deadlineHolder = $(el);
            $deadlineHolder.find('.deadlineAdder').click(() => {
                let html = $('.deadlineRowTemplate').html();
                html = html.replace(/TEMPLATE/, 'motions');
                let $newRow = $(html);
                $deadlineHolder.find('.deadlineList').append($newRow);

                $newRow.find('.datetimepicker').each((i, el) => {
                    $(el).datetimepicker({
                        locale: $(el).find("input").data('locale')
                    });
                });
            });
            $deadlineHolder.on('click', '.delRow', (ev) => {
                $(ev.currentTarget).parents('.deadlineEntry').remove();
            });
        });
    }

    private initSectionList() {
        let $list = $('#sectionsList'),
            newCounter = 0;

        $list.data("sortable", Sortable.create($list[0], {
            handle: '.drag-handle',
            animation: 150
        }));
        $list.on('click', 'a.remover', function (ev) {
            ev.preventDefault();
            let $sectionHolder = $(this).parents('li').first(),
                delId = $sectionHolder.data('id');
            bootbox.confirm(__t('admin', 'deleteMotionSectionConfirm'), function (result) {
                if (result) {
                    $('.adminTypeForm').append('<input type="hidden" name="sectionsTodelete[]" value="' + delId + '">');
                    $sectionHolder.remove();
                }
            });
        });
        $list.on('change', '.sectionType', function () {
            let $li = $(this).parents('li').first(),
                val = parseInt($(this).val());
            $li.removeClass('title textHtml textSimple image tabularData');
            if (val === 0) {
                $li.addClass('title');
            } else if (val === 1) {
                $li.addClass('textSimple');
            } else if (val === 2) {
                $li.addClass('textHtml');
            } else if (val === 3) {
                $li.addClass('image');
            } else if (val === 4) {
                $li.addClass('tabularData');
                if ($li.find('.tabularDataRow ul > li').length == 0) {
                    $li.find('.tabularDataRow .addRow').click().click().click();
                }
            }
        });
        $list.find('.sectionType').trigger('change');
        $list.on('change', '.maxLenSet', function () {
            let $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.addClass('maxLenSet').removeClass('no-maxLenSet');
            } else {
                $li.addClass('no-maxLenSet').removeClass('maxLenSet');
            }
        });
        $list.find('.maxLenSet').trigger('change');

        $('.sectionAdder').on('click', function (ev) {
            ev.preventDefault();
            let newStr = $('#sectionTemplate').html();
            newStr = newStr.replace(/#NEW#/g, 'new' + newCounter);
            let $newObj = $(newStr);
            $list.append($newObj);
            newCounter = newCounter + 1;

            $list.find('.sectionType').trigger('change');
            $list.find('.maxLenSet').trigger('change');

            let $tab = $newObj.find('.tabularDataRow ul');
            $tab.data("sortable", Sortable.create($tab[0], {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });

        let dataNewCounter = 0;
        $list.on('click', '.tabularDataRow .addRow', function (ev) {
            ev.preventDefault();
            let $this = $(this),
                $ul = $this.parent().find("ul"),
                $row = $($this.data('template').replace(/#NEWDATA#/g, 'new' + dataNewCounter));
            dataNewCounter = dataNewCounter + 1;
            $row.removeClass('no0').addClass('no' + $ul.children().length);
            $ul.append($row);
            $row.find('input').focus();
        });

        $list.on('click', '.tabularDataRow .delRow', function (ev) {
            let $this = $(this);
            ev.preventDefault();
            bootbox.confirm(__t('admin', 'deleteDataConfirm'), function (result) {
                if (result) {
                    $this.parents("li").first().remove();
                }
            });
        });

        $list.find('.tabularDataRow ul').each(function () {
            let $this = $(this);
            $this.data("sortable", Sortable.create(this, {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });
    }
}


new MotionTypeEdit();
