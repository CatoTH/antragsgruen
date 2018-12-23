export class CollectionItem {
    public type: string;
    public id: string;

    public getTrackId() {
        return this.type + "." + this.id;
    }
}
