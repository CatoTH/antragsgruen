// Type definitions for nestedSortable
// Project: http://mjsarfatti.com/sandbox/nestedSortable/
// Definitions by: Tobias Hößl <https://www.hoessl.eu/>
// Definitions:

/// <reference types="jquery" />


interface NestedSortableSettings {
    handle?: any;
    items?: string;
    toleranceElement?: string;
    placeholder?: string;
    tolerance?: string;
    forcePlaceholderSize?: boolean;
    helper?: string;
    axis?: 'x' |'y';
    update?: any;

}

interface NestedSortableStatic {

    /**
    * This method allows you to call nestedSortable without having to assign it to an element.
    */
    (settings: NestedSortableSettings): any;

    /**
    * Default settings used for Colorbox calls
    */
    settings: NestedSortableSettings;

}

interface NestedSortable {
    (): JQuery;
    (settings: NestedSortableSettings): JQuery;

}

interface JQueryStatic {
    nestedSortable: NestedSortableStatic;
}

interface JQuery {
    nestedSortable(settings: NestedSortableSettings): NestedSortable;
}
