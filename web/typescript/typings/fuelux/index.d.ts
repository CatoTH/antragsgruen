// Type definitions for fuelUX
// Project: http://getfuelux.com/
// Definitions by: Tobias Hößl <https://www.hoessl.eu/>
// Definitions:

/// <reference types="jquery" />


interface PillboxSettings {
}

interface PillboxStatic {

    /**
    * This method allows you to call Pillbox without having to assign it to an element.
    */
    (settings: PillboxSettings): any;

    /**
    * Default settings used for Colorbox calls
    */
    settings: PillboxSettings;

}

interface Pillbox {
    (): JQuery;
    (settings: PillboxSettings): JQuery;
}

interface SelectList {

}

interface PillboxItem {
    id: number | string;
    text: string;
}

interface JQueryStatic {
    pillbox: PillboxStatic;
}

interface JQuery {
    pillbox(): Pillbox;
    pillbox(methodName: 'items'): PillboxItem[];
    selectlist(): SelectList;
    selectlist(methodName: 'selectByValue', data: any): SelectList;
}
