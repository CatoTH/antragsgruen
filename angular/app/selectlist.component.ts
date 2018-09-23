import {Component, Input, Output, ElementRef, EventEmitter} from '@angular/core';

declare var $: any;

export interface SelectlistItem {
    id: string;
    title: string;
}

/**
 * Basically a wrapper for fuelux. Not exactly elegant, but works...
 */
@Component({
    selector: 'selectlist',
    templateUrl: './selectlist.component.html',
})
export class SelectlistComponent {
    @Input() items: SelectlistItem[];
    @Input() selected: string;
    @Output() onSelect: EventEmitter<SelectlistItem> = new EventEmitter<SelectlistItem>();

    private $el: any;

    public constructor(private el: ElementRef<Element>) {
        console.log("Constructing selectlist: ", el);
        console.log(this.items);
        this.$el = $(this.el.nativeElement);
        this.initSelectlistWrapper();
    }

    private initSelectlistWrapper()
    {
        if ($.fn.selectlist) {
            this.initSelectlist();
        } else {
            window.setTimeout(() => {
                this.initSelectlist();
            }, 100);
        }
    }

    private initSelectlist()
    {
        this.$el.selectlist();
        this.$el.on('changed.fu.selectlist', (ev, item) => {
            console.log(item);
            this.onSelect.emit({
                id: item.value.toString(),
                title: item.text,
            });
        });
    }
}
