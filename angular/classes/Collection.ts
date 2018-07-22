interface Collectible {
    id: string;
}

export class Collection<A extends Collectible> {
    public elements: {[id: string]: A} = {};

    public setElement(el: A) {
        this.elements[el.id] = el;
    }
}
