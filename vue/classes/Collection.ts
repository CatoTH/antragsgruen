import {Subject} from "rxjs";
import {CollectionItem} from "./CollectionItem";

export class Collection<A extends CollectionItem> {
    public elements: { [id: string]: A } = {};

    public changed$: Subject<boolean> = new Subject<boolean>();

    public elementUpdated$: Subject<A> = new Subject<A>();
    public elementDeleted$: Subject<string> = new Subject<string>();

    constructor(private classConstructor: new (data: string) => A) {
        this.elementUpdated$.subscribe(this.setElement.bind(this));
        this.elementDeleted$.subscribe(this.deleteElement.bind(this))
    }

    public setElement(data: string) {
        let obj: A = new this.classConstructor(data);
        this.elements[obj.id] = obj;
        this.changed$.next(true);
    }

    public deleteElement(elId: string) {
        delete this.elements[elId];
        this.changed$.next(true);
    }

    public setElements(arr: string[]) {
        if (!arr) {
            console.warn("Got non-array: ", arr);
        }
        arr.forEach((data) => {
            this.setElement(data);
        });
    }
}
