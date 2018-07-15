import {Motion} from "./Motion";

export class Collection {
    public elements: {[id: string]: Motion} = {};

    public setElement(el: Motion) {
        this.elements[el.id] = el;
    }
}
